<?php

namespace mint;

use mint\DbRepository\BalanceOperations;
use mint\DbRepository\ContentEntityRewards;
use mint\DbRepository\CurrencyTerminationPoints;
use mint\DbRepository\InventoryTypes;
use mint\DbRepository\Items;
use mint\DbRepository\ItemTerminationPoints;
use mint\DbRepository\ItemTransactionItemTypes;
use mint\DbRepository\ItemTransactions;
use mint\DbRepository\ItemTypes;
use mint\DbRepository\ItemOwnerships;

// termination points
function getCurrencyTerminationPointByName(string $name): ?array
{
    global $db;

    return current(CurrencyTerminationPoints::with($db)->getByColumn('name', $name));
}

function getItemTerminationPointByName(string $name): ?array
{
    global $db;

    return current(ItemTerminationPoints::with($db)->getByColumn('name', $name));
}

// balance
function getUserBalance(int $userId, bool $forUpdate = false): ?int
{
    global $mybb, $db;

    if (!$forUpdate && $userId === $mybb->user['uid']) {
        return $mybb->user['mint_balance'];
    } else {
        $conditions = 'uid = ' . (int)$userId;

        if ($forUpdate && in_array($db->type, ['pgsql', 'mysql'])) {
            $conditions .= ' FOR UPDATE';
        }

        $query = $db->simple_select('users', 'mint_balance', $conditions);

        if ($db->num_rows($query) == 1) {
            return (int)$db->fetch_field($query, 'mint_balance');
        } else {
            return null;
        }
    }
}

function getTopUsersByBalance(int $limit)
{
    global $db;

    return $db->simple_select('users', 'uid, username, usergroup, displaygroup, mint_balance', null, [
        'order_by' => 'mint_balance',
        'order_dir' => 'desc',
        'limit' => (int)$limit,
    ]);
}

// balance operations
function getBalanceOperations(?string $conditions = null)
{
    global $db;

    $query = $db->query("
        SELECT
            bo.*,
            bt.note, bt.private, bt.handler,
            tp.name AS currency_termination_point_name,
            u_from.uid AS from_user_id, u_from.username AS from_username,
            u_to.uid AS to_user_id, u_to.username AS to_username,
            iTr.id AS item_transaction_id
            FROM
                " . TABLE_PREFIX . "mint_balance_operations bo
                LEFT JOIN " . TABLE_PREFIX . "mint_currency_termination_points tp ON bo.currency_termination_point_id = tp.id
                LEFT JOIN " . TABLE_PREFIX . "mint_balance_transfers bt ON bo.balance_transfer_id = bt.id 
                LEFT JOIN " . TABLE_PREFIX . "users u_from ON bt.from_user_id = u_from.uid
                LEFT JOIN " . TABLE_PREFIX . "users u_to ON bt.to_user_id = u_to.uid
                LEFT JOIN " . TABLE_PREFIX . "mint_item_transactions iTr ON bt.id = iTr.balance_transfer_id
            {$conditions}
    ");

    return $query;
}

function countBalanceOperations(?string $conditions = null): int
{
    global $db;

    return $db->fetch_field(
        $db->query("
            SELECT
                COUNT(bo.id) AS n
                FROM
                    " . TABLE_PREFIX . "mint_balance_operations bo
                    LEFT JOIN " . TABLE_PREFIX . "mint_currency_termination_points tp ON bo.currency_termination_point_id = tp.id
                    LEFT JOIN " . TABLE_PREFIX . "mint_balance_transfers bt ON bo.balance_transfer_id = bt.id 
                    LEFT JOIN " . TABLE_PREFIX . "users u_from ON bt.from_user_id = u_from.uid
                    LEFT JOIN " . TABLE_PREFIX . "users u_to ON bt.to_user_id = u_to.uid
                {$conditions}
        "),
        'n'
    );
}

function getUserBalanceOperations(int $userId, ?string $conditions = null)
{
    return \mint\getBalanceOperations('WHERE bo.user_id = ' . (int)$userId . ' ' . $conditions);
}

function getRecentUserBalanceOperations(int $userId, int $limit)
{
    return \mint\getUserBalanceOperations(
        $userId,
        "ORDER BY id DESC LIMIT " . (int)$limit
    );
}

function getUserPublicBalanceOperations(int $userId, array $includePrivateWithUserIds = [], ?string $conditions = null)
{
    $whereString = 'bo.user_id = ' . (int)$userId;

    if (!empty($includePrivateWithUserIds)) {
        $csv = \mint\getIntegerCsv($includePrivateWithUserIds);

        $whereString .= ' AND (
            private IS NULL OR
            private = 0 OR
            from_user_id IN (' . $csv . ') OR
            to_user_id IN(' . $csv . ')
        )';
    } else {
         $whereString .= ' AND private IS NULL OR private = 0';
    }

    return \mint\getBalanceOperations('WHERE ' . $whereString . ' ' . $conditions);
}

function countUserPublicBalanceOperations(int $userId, array $includePrivateWithUserIds = [], ?string $conditions = null): int
{
    $whereString = 'bo.user_id = ' . (int)$userId;

    if (!empty($includePrivateWithUserIds)) {
        $csv = \mint\getIntegerCsv($includePrivateWithUserIds);

        $whereString .= ' AND (
            private IS NULL OR
            private = 0 OR
            from_user_id IN (' . $csv . ') OR
            to_user_id IN(' . $csv . ')
        )';
    } else {
         $whereString .= ' AND private IS NULL OR private = 0';
    }

    return \mint\countBalanceOperations('WHERE ' . $whereString . ' ' . $conditions);
}

function userBalanceOperationWithTerminationPoint($user, int $value, string $terminationPointName, bool $allowOverdraft = true, bool $useDbTransaction = true): bool
{
    global $db;

    if (is_array($user)) {
        $userId = (int)$user['uid'];
    } else {
        $userId = (int)$user;
    }

    $terminationPointId = \mint\getCurrencyTerminationPointByName($terminationPointName)['id'] ?? null;

    if ($terminationPointId !== null) {
        $result = BalanceOperations::with($db)->execute($userId, $value, [
            'currency_termination_point_id' => $terminationPointId,
        ], $useDbTransaction, $allowOverdraft);

        return $result;
    } else {
        return false;
    }
}

// balance transfers
function getBalanceTransfers(?string $conditions = null)
{
    global $db;

    $query = $db->query("
        SELECT
            bt.*,
            u_from.uid AS from_user_id, u_from.username AS from_username,
            u_to.uid AS to_user_id, u_to.username AS to_username,
            iTr.id AS item_transaction_id
            FROM
                " . TABLE_PREFIX . "mint_balance_transfers bt
                LEFT JOIN " . TABLE_PREFIX . "users u_from ON bt.from_user_id = u_from.uid
                LEFT JOIN " . TABLE_PREFIX . "users u_to ON bt.to_user_id = u_to.uid
                LEFT JOIN " . TABLE_PREFIX . "mint_item_transactions iTr ON bt.id = iTr.balance_transfer_id
            {$conditions}
    ");

    return $query;
}

function getRecentPublicBalanceTransfers(int $limit)
{
    return \mint\getBalanceTransfers(
        "WHERE private = 0 ORDER BY id DESC LIMIT " . (int)$limit
    );
}

// content entity rewards
function addContentEntityReward(string $rewardSourceName, int $contentEntityId, int $userId, bool $restoreOnly = false): bool
{
    global $db;

    $rewardSource = \mint\getRegisteredRewardSources()[$rewardSourceName] ?? null;

    if ($rewardSource && $rewardSource['reward']() != 0) {
        $terminationPointId = \mint\getCurrencyTerminationPointByName($rewardSource['terminationPoint'])['id'] ?? null;

        if ($terminationPointId !== null) {
            $entry = $db->fetch_array(
                ContentEntityRewards::with($db)->get('*', "WHERE
                    user_id = " . (int)$userId . " AND
                    content_type = '" . $db->escape_string($rewardSource['contentType']) . "' AND
                    content_entity_id = " . (int)$contentEntityId . " AND
                    currency_termination_point_id = " . (int)$terminationPointId . "
                ")
            );

            if ($entry) {
                if ($entry['void'] == 1) {
                    $value = $entry['value'];

                    ContentEntityRewards::with($db)->updateById($entry['id'], [
                        'last_action_date' => \TIME_NOW,
                        'void' => false,
                    ]);
                } else {
                    return false;
                }
            } elseif (!$restoreOnly) {
                $baseValue = $rewardSource['reward']();

                $multiplier = \mint\getUserRewardMultiplier($userId, $rewardSource);

                $value = \mint\getMultipliedRewardValue($baseValue, $multiplier);

                ContentEntityRewards::with($db)->insert([
                    'user_id' => $userId,
                    'content_type' => $rewardSource['contentType'],
                    'content_entity_id' => $contentEntityId,
                    'currency_termination_point_id' => $terminationPointId,
                    'base_value' => $baseValue,
                    'multiplier' => $multiplier,
                    'value' => $value,
                    'last_action_date' => \TIME_NOW,
                    'void' => false,
                ]);
            } else {
                return false;
            }

            \mint\userBalanceOperationWithTerminationPoint($userId, $value, $rewardSource['terminationPoint']);

            return true;
        } else {
            return false;
        }
    } else {
        return false;
    }
}

function voidContentEntityReward(string $rewardSourceName, int $contentEntityId): bool
{
    global $db;

    $rewardSource = \mint\getRegisteredRewardSources()[$rewardSourceName] ?? null;

    if ($rewardSource) {
        $terminationPointId = \mint\getCurrencyTerminationPointByName($rewardSource['terminationPoint'])['id'] ?? null;

        if ($terminationPointId !== null) {
            $entries = \mint\queryResultAsArray(
                ContentEntityRewards::with($db)->get('*', "WHERE
                    content_type = '" . $db->escape_string($rewardSource['contentType']) . "' AND
                    content_entity_id = " . (int)$contentEntityId . " AND
                    currency_termination_point_id = " . (int)$terminationPointId . "
                ")
            );

            foreach ($entries as $entry) {
                if ($entry) {
                    if ($entry['void'] == 0) {
                        $result = ContentEntityRewards::with($db)->update([
                            'last_action_date' => \TIME_NOW,
                            'void' => true,
                        ], "
                            user_id = " . (int)$entry['user_id'] . " AND
                            content_type = '" . $db->escape_string($rewardSource['contentType']) . "' AND
                            content_entity_id = " . (int)$contentEntityId . " AND
                            currency_termination_point_id = " . (int)$terminationPointId . " 
                        ");

                        \mint\userBalanceOperationWithTerminationPoint($entry['user_id'], -$entry['value'], $rewardSource['terminationPoint']);
                    }
                }
            }

            return true;
        } else {
            return false;
        }
    } else {
        return false;
    }
}

function getUserRewardMultiplier(int $userId, array $rewardSource): ?float
{
    global $plugins;

    static $usersMultipliers;

    if (!isset($usersMultipliers[$userId])) {
        $user = \get_user($userId);

        if (!empty($user)) {
            $userGroupIds = array_map('intval', explode(',', $user['additionalgroups']));
            $userGroupIds[] = $user['usergroup'];

            $usersMultipliers[$userId] = \mint\getArraySubset(
                \mint\getUsergroupRewardMultipliers(),
                $userGroupIds
            );
        } else {
            $usersMultipliers[$userId] = null;
        }
    }

    $userMultipliers = $usersMultipliers[$userId];

    if ($userMultipliers) {
        $arguments = compact('userId', 'rewardSource', 'userMultipliers');

        extract(
            $plugins->run_hooks('mint_get_user_reward_multiplier', $arguments)
        );

        if (empty($userMultipliers)) {
            return null;
        } else {
            return max($userMultipliers);
        }
    } else {
        return null;
    }
}

function getUsergroupRewardMultipliers(): array
{
    global $cache;

    return array_column(
        $cache->read('usergroups') ?? [],
        'mint_reward_multiplier',
        'gid'
    );
}

// inventory
function getUserInventoryType($user): ?array
{
    global $db;

    if ($user['mint_inventory_type_id']) {
        $inventoryTypeId = $user['mint_inventory_type_id'];
    } else {
        $inventoryTypeId = \mint\getSettingValue('default_inventory_type_id');
    }

    $userInventoryType = InventoryTypes::with($db)->getById($inventoryTypeId);

    return $userInventoryType;
}

function getUserInventoryData($user): ?array
{
    if (!is_array($user)) {
        $user = \get_user($user);

        if (!$user) {
            return null;
        }
    }

    $userInventoryType = \mint\getUserInventoryType($user);

    if ($userInventoryType) {
        $slots = $userInventoryType['slots'] + $user['mint_inventory_slots_bonus'];

        $userInventoryData = [
            'id' => $userInventoryType['id'],
            'title' => $userInventoryType['title'],
            'slots' => $slots,
            'slotsOccupied' => $user['mint_inventory_slots_occupied'],
            'slotsAvailable' => $slots - $user['mint_inventory_slots_occupied'],
        ];
    } else {
        $userInventoryData = null;

        $userInventoryData = [
            'id' => null,
            'title' => null,
            'slots' => null,
            'slotsOccupied' => $user['mint_inventory_slots_occupied'],
            'slotsAvailable' => 0,
        ];
    }

    return $userInventoryData;
}

function getOccupiedUserInventorySlots(int $userId, bool $cached = true, bool $forUpdate = false): ?int
{
    global $mybb, $db;

    if ($cached || $forUpdate) {
        if (!$forUpdate && $userId === $mybb->user['uid']) {
            $count = (int)$mybb->user['mint_inventory_slots_occupied'];
        } else {
            $conditions = 'uid = ' . (int)$userId;

            if ($forUpdate && in_array($db->type, ['pgsql', 'mysql'])) {
                $conditions .= ' FOR UPDATE';
            }

            $query = $db->simple_select('users', 'mint_inventory_slots_occupied', $conditions);

            if ($db->num_rows($query) == 1) {
                $count = (int)$db->fetch_field($query, 'mint_inventory_slots_occupied');
            } else {
                $count = null;
            }
        }
    } else {
        $count = current(\mint\countOccupiedUserInventorySlots([
            $userId,
        ]));
    }

    return $count;
}

function recountOccupiedUserInventorySlots(?array $userIds = null, ?array $itemTypeId = null): void
{
    $userCounts = \mint\countOccupiedUserInventorySlots($userIds, $itemTypeId);

    foreach ($userCounts as $userId => $userCount) {
        \mint\updateUser($userId, [
            'mint_inventory_slots_occupied' => $userCount,
        ]);
    }
}

function countOccupiedUserInventorySlots(?array $userIds = null, ?array $itemTypeIds = null): ?array
{
    global $db;

    if ($userIds === [] || $itemTypeIds === []) {
        return [];
    }

    $whereConditions = [
        'io.active = 1',
    ];

    if ($userIds !== null) {
        $whereConditions[] = 'io.user_id IN (' . \mint\getIntegerCsv($userIds) . ')';
    }

    if ($itemTypeIds !== null) {
        $whereConditions[] = 'it.id IN (' . \mint\getIntegerCsv($itemTypeIds) . ')';
    }

    $where = implode(' AND ', $whereConditions);

    $query = $db->query("
        SELECT
            user_id, SUM(n) AS n
            FROM
                (
                    SELECT
                        io.user_id, COUNT(io.id) AS n
                        FROM
                            " . TABLE_PREFIX . "mint_item_ownerships io
                            INNER JOIN " . TABLE_PREFIX . "mint_items i ON io.item_id = i.id
                            INNER JOIN " . TABLE_PREFIX . "mint_item_types it ON i.item_type_id = it.id
                        WHERE it.stacked = 0 AND {$where}
                        GROUP BY io.user_id
                    UNION ALL
                    SELECT
                        io.user_id, COUNT(DISTINCT it.id) AS n
                        FROM
                            " . TABLE_PREFIX . "mint_item_ownerships io
                            INNER JOIN " . TABLE_PREFIX . "mint_items i ON io.item_id = i.id
                            INNER JOIN " . TABLE_PREFIX . "mint_item_types it ON i.item_type_id = it.id
                        WHERE it.stacked = 1 AND {$where}
                        GROUP BY io.user_id
                ) itemCountsByStackedStatus
            GROUP BY user_id
    ");

    $result = \mint\queryResultAsArray($query, 'user_id', 'n');

    if ($userIds !== null) {
        $result += array_fill_keys($userIds, 0);
    }

    return $result;
}

function getRequiredUserInventorySlotsForItems(int $userId, array $items): int
{
    $slotsRequired = 0;

    $distinctUserItemTypeIds = \mint\getDistinctItemTypeIdsByUser($userId);

    foreach ($items as $item) {
        $inArray = in_array($item['item_type_id'], $distinctUserItemTypeIds);

        if (!$item['item_type_stacked'] || !$inArray) {
            $slotsRequired++;
        }

        if (!$inArray) {
            $distinctUserItemTypeIds[] = $item['item_type_id'];
        }
    }

    return $slotsRequired;
}

function countAvailableUserInventorySlotsWithItems(int $userId, array $items): int
{
    $bidUserInventory = \mint\getUserInventoryData($userId);
    $slotsRequired = \mint\getRequiredUserInventorySlotsForItems($userId, $items);

    $slotsWithItems = $bidUserInventory['slotsAvailable'] - $slotsRequired;

    return $slotsWithItems;
}

// items
function getItemsById(array $ids, bool $forUpdate = false)
{
    global $db;

    if (!empty($ids)) {
        $conditions = 'id IN (' . \mint\getIntegerCsv($ids) . ')';

        if ($forUpdate && in_array($db->type, ['pgsql', 'mysql'])) {
            $conditions .= ' FOR UPDATE';
        }

        $query = $db->simple_select('mint_items', '*', $conditions);

        return $query;
    } else {
        return null;
    }
}

// item ownerships
function getItemOwnershipsById(array $ids, bool $forUpdate = false): ?array
{
    global $db;

    if (!empty($ids)) {
        $conditions = 'id IN (' . \mint\getIntegerCsv($ids) . ')';

        if ($forUpdate && in_array($db->type, ['pgsql', 'mysql'])) {
            $conditions .= ' FOR UPDATE';
        }

        $query = $db->simple_select('mint_item_ownerships', '*', $conditions);

        $result = \mint\queryResultAsArray($query);

        return $result;
    } else {
        return null;
    }
}

function getItemOwnershipWithDetails(int $id): ?array
{
    $keyItem = current(\mint\getItemOwnershipsDetails([
        $id,
    ]));

    $items = \mint\getItemOwnershipsWithDetails(
        null,
        [
            $id,
        ],
        null,
        true
    );


    $items = array_filter($items, function ($item) use ($keyItem) {
        return $item['item_transaction_id'] == $keyItem['item_transaction_id'];
    });

    if (count($items) == 1) {
        $item = current($items);
    } else {
        $item = null;
    }

    return $item;
}

function getItemOwnershipsWithDetails(?int $userId, ?array $itemOwnershipIds = null, ?int $mostRecent = null, bool $groupByTransaction = false, bool $showUserStack = true, bool $activeOnly = true, bool $transactionCandidatesOnly = false): array
{
    $itemOwnerships = [];

    $userItemOwnershipsWithStackedAmount = \mint\getItemOwnershipsWithStackedAmount(
        $userId,
        $itemOwnershipIds,
        $mostRecent,
        $groupByTransaction,
        $showUserStack,
        $activeOnly,
        $transactionCandidatesOnly
    );

    $userItemOwnershipsDetails = \mint\getItemOwnershipsDetails(
        array_keys($userItemOwnershipsWithStackedAmount)
    );

    foreach ($userItemOwnershipsWithStackedAmount as $entry) {
        $itemOwnerships[ $entry['item_ownership_id'] ] = array_merge($entry, $userItemOwnershipsDetails[$entry['item_ownership_id']]);
    }

    return $itemOwnerships;
}

function getItemOwnershipsWithStackedAmount(?int $userId, ?array $itemOwnershipIds = null, ?int $mostRecent = null, bool $groupByTransaction = false, bool $showUserStack = true, bool $activeOnly = true, bool $transactionCandidatesOnly = false): array
{
    global $db;

    if (!empty($itemOwnershipIds)) {
        $csv = \mint\getIntegerCsv($itemOwnershipIds);

        $query = $db->query("
            SELECT
                io.id AS item_ownership_id, io.user_id, i.item_type_id, it.stacked AS item_type_stacked, iTrI.item_transaction_id
                FROM
                    " . TABLE_PREFIX . "mint_item_ownerships io
                    INNER JOIN " . TABLE_PREFIX . "mint_items i ON io.item_id = i.id
                    INNER JOIN " . TABLE_PREFIX . "mint_item_types it ON i.item_type_id = it.id
                    LEFT JOIN (
                        " . TABLE_PREFIX . "mint_item_transaction_items iTrI
                        INNER JOIN " . TABLE_PREFIX . "mint_item_transactions iTr ON iTrI.item_transaction_id = iTr.id AND iTr.active = 1 
                    ) ON iTrI.item_id = i.id
                    WHERE io.id IN (" . $csv . ")
        ");

        $stackedTypeIds = [];
        $userIds = [];
        $nonStackedItemUserIds = [];

        while ($row = $db->fetch_array($query)) {
            if ($row['item_type_stacked']) {
                $stackedTypeIds[] = (int)$row['item_type_id'];
                $userIds[] = (int)$row['user_id'];
            } else {
                $nonStackedItemUserIds[] = (int)$row['item_ownership_id'];
            }
        }

        if ($stackedTypeIds && $userIds) {
            if ($showUserStack) {
                $stackedWhereConditions = 'AND (
                    it.id IN (' . implode(',', $stackedTypeIds) . ') AND io.user_id IN (' . implode(',', $userIds) . ')
                )';
            } else {
                $stackedWhereConditions = 'AND io.id IN (' . implode(',', $itemOwnershipIds) . ')';
            }
        } else {
            $stackedWhereConditions = null;
        }

        if ($nonStackedItemUserIds) {
            $nonStackedWhereConditions = 'AND io.id IN (' . implode(',', $nonStackedItemUserIds) . ')';
        } else {
            $nonStackedWhereConditions = null;
        }
    } else {
        $stackedWhereConditions = 'AND user_id = ' . (int)$userId;
        $nonStackedWhereConditions = 'AND user_id = ' . (int)$userId;
    }

    $unionQueries = [];

    $groupByColumns = [];
    $tableJoins = null;
    $whereConditions = null;
    $groupBy = null;

    if ($groupByTransaction || $transactionCandidatesOnly) {
        $tableJoins = "
            LEFT JOIN (
                " . TABLE_PREFIX . "mint_item_transaction_items iTrI
                INNER JOIN " . TABLE_PREFIX . "mint_item_transactions iTr ON iTrI.item_transaction_id = iTr.id AND iTr.active = 1 
            ) ON iTrI.item_id = i.id";
        $groupByColumns[] = 'iTr.id';
    }

    if ($activeOnly) {
        $whereConditions .= ' AND io.active = 1';
    }

    if ($transactionCandidatesOnly) {
        $whereConditions .= ' AND it.transferable = 1 AND iTr.active IS NULL';
    }

    if ($nonStackedWhereConditions) {
        $unionQueries[] = "(
            SELECT
                io.id AS item_ownership_id, io.activation_date, NULL AS stacked_amount
                FROM
                    " . TABLE_PREFIX . "mint_item_ownerships io
                    INNER JOIN " . TABLE_PREFIX . "mint_items i ON io.item_id = i.id
                    INNER JOIN " . TABLE_PREFIX . "mint_item_types it ON i.item_type_id = it.id
                    {$tableJoins}
                WHERE it.stacked = 0 {$whereConditions} {$nonStackedWhereConditions}
        )";
    }

    if ($stackedWhereConditions) {
        $groupByColumns[] = 'i.item_type_id';

        $groupBy = 'GROUP BY ' . implode(',', $groupByColumns);

        $unionQueries[] = "(
            SELECT
                MIN(io.id) AS item_ownership_id, MAX(io.activation_date) AS activation_date, COUNT(io.id) AS stacked_amount
                FROM
                    " . TABLE_PREFIX . "mint_item_ownerships io
                    INNER JOIN " . TABLE_PREFIX . "mint_items i ON io.item_id = i.id
                    INNER JOIN " . TABLE_PREFIX . "mint_item_types it ON i.item_type_id = it.id
                    {$tableJoins}
                WHERE it.stacked = 1 {$whereConditions} {$stackedWhereConditions}
                {$groupBy}
        )";
    }

    if ($unionQueries) {
        if ($mostRecent !== null) {
            $conditions = 'ORDER BY activation_date DESC LIMIT ' . (int)$mostRecent;
        } else {
            $conditions = null;
        }

        $query = implode(' UNION ALL ', $unionQueries);

        $result = \mint\queryResultAsArray(
            $db->query($query . $conditions),
            'item_ownership_id'
        );
    } else {
        $result = [];
    }

    return $result;
}

function getItemsDetails(array $itemIds, bool $withActiveTransaction = true): array
{
    global $db;

    if (!empty($itemIds)) {
        $csv = \mint\getIntegerCsv($itemIds);

        $columns = null;
        $joinStatements = null;

        if ($withActiveTransaction) {
            $columns = ', iTr.id AS item_transaction_id';
            $joinStatements .= "
                LEFT JOIN (
                    " . TABLE_PREFIX . "mint_item_transaction_items iTrI
                    INNER JOIN " . TABLE_PREFIX . "mint_item_transactions iTr ON iTrI.item_transaction_id = iTr.id AND iTr.active = 1 
                ) ON iTrI.item_id = i.id
            ";
        }

        return \mint\queryResultAsArray(
            $db->query("
                SELECT
                    i.id AS item_id, i.item_type_id, i.active AS item_active, i.activation_date AS item_activation_date,
                    io.id AS item_ownership_id, io.item_id, io.user_id, io.active AS item_ownership_active, io.activation_date, io.deactivation_date,
                    iTy.title AS item_type_title, iTy.description AS item_type_description, iTy.image AS item_type_image, iTy.stacked AS item_type_stacked, iTy.transferable AS item_type_transferable, iTy.discardable AS item_type_discardable,
                    ic.title AS item_category_title
                    {$columns}
                    FROM
                        " . TABLE_PREFIX . "mint_items i
                        INNER JOIN " . TABLE_PREFIX . "mint_item_types iTy ON i.item_type_id = iTy.id
                        INNER JOIN " . TABLE_PREFIX . "mint_item_categories ic ON iTy.item_category_id = ic.id
                        LEFT JOIN " . TABLE_PREFIX . "mint_item_ownerships io ON i.id = io.item_id
                        {$joinStatements}
                    WHERE i.id IN (" . $csv . ")
            "),
            'item_id'
        );
    } else {
        return [];
    }
}

function getItemOwnershipsDetails(array $itemOwnershipIds): array
{
    global $db;

    if (!empty($itemOwnershipIds)) {
        $csv = \mint\getIntegerCsv($itemOwnershipIds);

        return \mint\queryResultAsArray(
            $db->query("
                SELECT
                    io.id AS item_ownership_id, io.item_id, io.user_id, io.active AS item_ownership_active, io.activation_date, io.deactivation_date,
                    u.username AS user_username,
                    i.item_type_id, i.active AS item_active, i.activation_date AS item_activation_date,
                    iTy.name AS item_type_name, iTy.title AS item_type_title, iTy.description AS item_type_description, iTy.image AS item_type_image, iTy.stacked AS item_type_stacked, iTy.transferable AS item_type_transferable, iTy.discardable AS item_type_discardable,
                    ic.title AS item_category_title,
                    iTr.id AS item_transaction_id
                    FROM
                        " . TABLE_PREFIX . "mint_item_ownerships io
                        INNER JOIN " . TABLE_PREFIX . "mint_items i ON io.item_id = i.id
                        INNER JOIN " . TABLE_PREFIX . "users u ON io.user_id = u.uid
                        INNER JOIN " . TABLE_PREFIX . "mint_item_types iTy ON i.item_type_id = iTy.id
                        INNER JOIN " . TABLE_PREFIX . "mint_item_categories ic ON iTy.item_category_id = ic.id
                        LEFT JOIN (
                            " . TABLE_PREFIX . "mint_item_transaction_items iTrI
                            INNER JOIN " . TABLE_PREFIX . "mint_item_transactions iTr ON iTrI.item_transaction_id = iTr.id AND iTr.active = 1 
                        ) ON iTrI.item_id = i.id
                    WHERE io.id IN (" . $csv . ")
            "),
            'item_ownership_id'
        );
    } else {
        return [];
    }
}

function getItemIdsByResolvedOwnershipStackedAmount(array $itemOwnershipIdsWithStackedAmount, bool $transactionCandidatesOnly = false): ?array
{
    $items = [];

    $userItemOwnershipsDetails = \mint\getItemOwnershipsDetails(
        array_keys($itemOwnershipIdsWithStackedAmount)
    );

    foreach ($itemOwnershipIdsWithStackedAmount as $itemOwnershipId => $stackedAmount) {
        if ($stackedAmount > 0) {
            $itemOwnershipDetails = &$userItemOwnershipsDetails[$itemOwnershipId];

            if (isset($itemOwnershipDetails)) {
                if ($itemOwnershipDetails['item_type_stacked']) {
                    $itemTypeOwnerships = \mint\getItemOwnershipsByItemTypeAndUser($itemOwnershipDetails['item_type_id'], $itemOwnershipDetails['user_id'], $stackedAmount, $transactionCandidatesOnly);

                    if (count($itemTypeOwnerships) != $stackedAmount) {
                        return null;
                    } else {
                        foreach ($itemTypeOwnerships as $itemTypeOwnership) {
                            $items[] = [
                                'item_ownership_id' => $itemTypeOwnership['item_ownership_id'],
                                'item_id' => $itemTypeOwnership['item_id'],
                            ];
                        }
                    }
                } else {
                    $items[] = [
                        'item_ownership_id' => $itemOwnershipDetails['item_ownership_id'],
                        'item_id' => $itemOwnershipDetails['item_id'],
                    ];
                }

            } else {
                return null;
            }
        }
    }

    return $items;
}

function getItemTypeAmountsByOwnershipIdsWithAmount(array $ownershipIdsWithAmount): array
{
    $itemTypeIdsWithAmount = [];

    $itemOwnershipsDetails = \mint\getItemOwnershipsDetails(
        array_keys($ownershipIdsWithAmount)
    );

    foreach ($ownershipIdsWithAmount as $itemOwnershipId => $amount) {
        if (isset($itemOwnershipsDetails[$itemOwnershipId])) {
            $value = &$itemTypeIdsWithAmount[ $itemOwnershipsDetails[$itemOwnershipId]['item_type_id'] ];

            if (!isset($value)) {
                $value = 0;
            }

            $value += $amount;
        }
    }

    return $itemTypeIdsWithAmount;
}

function getUserItemsDetailsByItemTypeAndResolvedAmount(array $itemTypeIdsWithAmount, int $userId): ?array
{
    $entries = [];

    foreach ($itemTypeIdsWithAmount as $itemTypeId => $amount) {
        $itemTypeOwnerships = \mint\getItemOwnershipsByItemTypeAndUser($itemTypeId, $userId, $amount);

        if (count($itemTypeOwnerships) == $amount) {
            $itemsDetails = \mint\getItemsDetails(
                array_column($itemTypeOwnerships, 'item_id'),
                false
            );

            $entries = array_merge($entries, $itemsDetails);
        } else {
            return null;
        }
    }

    return $entries;
}

function getItemOwnershipsByItemTypeAndUser(int $itemTypeId, int $userId, ?int $limit = null, bool $transactionCandidatesOnly = false): array
{
    global $db;

    $conditions = [];
    $whereConditions = 'iTy.id = ' . (int)$itemTypeId . ' AND io.user_id = ' . (int)$userId . ' AND io.active = 1';
    
    if ($transactionCandidatesOnly) {
        $tableJoins = "
            LEFT JOIN (
                " . TABLE_PREFIX . "mint_item_transaction_items iTrI
                INNER JOIN " . TABLE_PREFIX . "mint_item_transactions iTr ON iTrI.item_transaction_id = iTr.id AND iTr.active = 1 
            ) ON iTrI.item_id = i.id";
        $whereConditions .= ' AND iTrI.item_transaction_id IS NULL';
    } else {
        $tableJoins = null;
    }

    if ($limit !== null) {
        $conditions[] = 'LIMIT ' . (int)$limit;
    }

    $conditions = implode(' ', $conditions);

    return \mint\queryResultAsArray(
        $db->query("
            SELECT
                io.id AS item_ownership_id, i.id AS item_id, iTy.id AS item_type_id
                FROM
                    " . TABLE_PREFIX . "mint_item_ownerships io
                    INNER JOIN " . TABLE_PREFIX . "mint_items i ON io.item_id = i.id
                    INNER JOIN " . TABLE_PREFIX . "mint_item_types iTy ON i.item_type_id = iTy.id
                    {$tableJoins}
                WHERE {$whereConditions}
                ORDER BY io.activation_date DESC
                {$conditions}
        "),
        'item_ownership_id'
    );
}

function getDistinctItemTypeIdsByUser(int $userId): array
{
    global $db;

    return \mint\queryResultAsArray(
        $db->query("
            SELECT
                DISTINCT item_type_id
                FROM
                    " . TABLE_PREFIX . "mint_item_ownerships io
                    INNER JOIN " . TABLE_PREFIX . "mint_items i ON io.item_id = i.id
                WHERE user_id = " . (int)$userId . " AND io.active = 1
        "),
        null,
        'item_type_id'
    );
}

function getItemTypeOwnershipUserIds(array $itemTypeIds): array
{
    global $db;

    if (!empty($itemTypeIds)) {
        return \mint\queryResultAsArray(
            $db->query("
                SELECT
                    DISTINCT user_id
                    FROM
                        " . TABLE_PREFIX . "mint_item_ownerships io
                        INNER JOIN " . TABLE_PREFIX . "mint_items i ON io.item_id = i.id
                    WHERE item_type_id = " . \mint\getIntegerCsv($itemTypeIds) . " AND io.active = 1
            "),
            null,
            'user_id'
        );
    } else {
        return [];
    }
}

function createItemsWithTerminationPoint(int $itemTypeId, int $amount, int $userId, string $terminationPointName, bool $useDbTransaction = true): bool
{
    global $db;

    $terminationPointId = \mint\getItemTerminationPointByName($terminationPointName)['id'] ?? null;

    if ($terminationPointId !== null) {
        $itemType = ItemTypes::with($db)->getById($itemTypeId);

        if ($itemType !== null) {
            if ($useDbTransaction) {
                $db->write_query('BEGIN');
            }

            $result = true;

            $items = [];

            for ($i = 1; $i <= $amount; $i++) {
                $itemId = Items::with($db)->create($itemTypeId, $terminationPointId);

                if ($itemId) {
                    $items[] = [
                        'item_id' => $itemId,
                        'item_type_id' => $itemTypeId,
                        'item_type_stacked' => $itemType['stacked'],
                    ];
                } else {
                    $result &= false;
                    break;
                }
            }

            if ($result == true) {
                $result &= ItemOwnerships::with($db)->assign($items, $userId);
            }

            if ($useDbTransaction) {
                if ($result == true) {
                    $db->write_query('COMMIT');
                } else {
                    $db->write_query('ROLLBACK');
                }
            }

            return $result;
        } else {
            return false;
        }
    } else {
        return false;
    }
}

function removeItemsWithTerminationPoint(int $itemOwnershipId, int $stackedAmount, string $terminationPointName, bool $useDbTransaction = true): bool
{
    global $db;

    $terminationPointId = \mint\getItemTerminationPointByName($terminationPointName)['id'] ?? null;

    if ($terminationPointId !== null) {
        $itemOwnershipDetails = \mint\getItemOwnershipWithDetails($itemOwnershipId);

        if ($itemOwnershipDetails !== null) {
            if ($useDbTransaction) {
                $db->write_query('BEGIN');
            }

            if ($itemOwnershipDetails['item_type_stacked']) {
                $itemTypeOwnerships = \mint\getItemOwnershipsByItemTypeAndUser($itemOwnershipDetails['item_type_id'], $itemOwnershipDetails['user_id'], $stackedAmount);

                $itemIds = array_column($itemTypeOwnerships, 'item_id');
            } else {
                $itemIds = [
                    $itemOwnershipDetails['item_id']
                ];
            }

            $result = (bool)\mint\getItemsById($itemIds, true);

            if ($result === true) {
                foreach ($itemIds as $itemId) {
                    $result &= Items::with($db)->remove($itemId, $terminationPointId);
                }
            }

            if ($useDbTransaction) {
                if ($result == true) {
                    $db->write_query('COMMIT');
                } else {
                    $db->write_query('ROLLBACK');
                }
            }

            return $result;
        } else {
            return false;
        }
    } else {
        return false;
    }
}

// item transactions
function getItemTransactionById(int $transactionId, bool $forUpdate = false): ?array
{
    global $db;

    $conditions = 'id = ' . (int)$transactionId;

    if ($forUpdate && in_array($db->type, ['pgsql', 'mysql'])) {
        $conditions .= ' FOR UPDATE';
    }

    $query = $db->simple_select('mint_item_transactions', '*', $conditions);

    if ($db->num_rows($query) == 1) {
        return $db->fetch_array($query);
    } else {
        return null;
    }
}

function getItemTransactionsDetails(?string $conditions, bool $withItems = false): array
{
    global $db;

    $transactions = \mint\queryResultAsArray(
        $db->query("
            SELECT
                iTr.*,
                ask_u.username AS ask_user_username,
                bid_u.username AS bid_user_username
                FROM
                    " . TABLE_PREFIX . "mint_item_transactions iTr
                    LEFT JOIN " . TABLE_PREFIX . "users ask_u ON iTr.ask_user_id = ask_u.uid 
                    LEFT JOIN " . TABLE_PREFIX . "users bid_u ON iTr.bid_user_id = bid_u.uid
                " . $conditions . "
        "),
        'id'
    );

    if ($withItems) {
        $transactionsItems = \mint\getItemTransactionsItems(
            array_keys($transactions),
            true
        );

        $transactionsAskItemTypes = \mint\getItemTransactionsAskItemTypes(array_keys($transactions));

        foreach ($transactions as $transactionId => &$transaction) {
            foreach ($transactionsItems[$transactionId] as $item) {
                if (!$transaction['active']) {
                    $item['item_transaction_id'] = null;
                }

                if ($item['bid'] == 1) {
                    $type = 'bid_items';
                } else {
                    $type = 'offered_items';
                }

                $transaction[$type][] = $item;
            }

            $transaction['ask_item_types'] = $transactionsAskItemTypes[$transactionId];
        }
    }

    return $transactions;
}

function getItemTransactionDetails(int $transactionId, bool $withItems = false): ?array
{
    return \mint\getItemTransactionsDetails('WHERE iTr.id = ' . (int)$transactionId, $withItems)[$transactionId];
}

function getUserActiveTransactions(int $userId): array
{
    return \mint\getItemTransactionsDetails('WHERE active = 1 AND ask_user_id = ' . (int)$userId . ' ORDER BY ask_date DESC', true);
}

function getActivePublicItemTransactions(?string $conditions): array
{
    $passedConditions = 'WHERE iTr.active = 1 AND iTr.unlisted = 0';

    if ($conditions) {
        $passedConditions .= ' ' . $conditions;
    }

    $entries = \mint\getItemTransactionsDetails($passedConditions, true);

    return $entries;
}

function countActivePublicItemTransactions(): int
{
    global $db;

    return ItemTransactions::with($db)->count('active = 1 AND unlisted = 0');
}

function getRecentActivePublicItemTransactions(?int $limit = null): array
{
    $conditions = 'ORDER BY iTr.ask_date DESC';

    if ($limit) {
        $conditions .= ' LIMIT ' . (int)$limit;
    }

    $entries = \mint\getActivePublicItemTransactions($conditions);

    return $entries;
}

function getRecentCompletedPublicItemTransactions(int $limit): array
{
    return \mint\getItemTransactionsDetails('WHERE iTr.completed = 1 ORDER BY iTr.completed_date DESC LIMIT ' . (int)$limit, true);
}

// item transaction items
function getItemTransactionsItems(array $transactionIds, bool $withDetails = false, ?int $bid = null): array
{
    global $db;

    if (!empty($transactionIds)) {
        $transactionsItems = array_fill_keys($transactionIds, []);

        $csv = \mint\getIntegerCsv($transactionIds);

        $itemOwnershipsJoinConditions = null;

        $where = 'iTrI.item_transaction_id IN (' . $csv . ')';

        if ($bid === 0) {
            $where .= ' AND iTrI.bid = 0';
        } elseif ($bid === 1) {
            $where .= ' AND iTrI.bid = 1';
        }

        $entries = \mint\queryResultAsArray(
            $db->query("
                SELECT
                    iTrI.item_id, iTrI.item_transaction_id, iTrI.bid,
                    i.item_type_id, i.active AS item_active,
                    iTy.transferable AS item_type_transferable,
                    io.id AS item_ownership_id, io.user_id, io.active AS item_ownership_active
                    FROM
                        " . TABLE_PREFIX . "mint_item_transaction_items iTrI
                        INNER JOIN " . TABLE_PREFIX . "mint_items i ON iTrI.item_id = i.id
                        INNER JOIN " . TABLE_PREFIX . "mint_item_types iTy ON i.item_type_id = iTy.id
                        LEFT JOIN " . TABLE_PREFIX . "mint_item_ownerships io ON i.id = io.item_id AND io.active = 1
                    WHERE {$where}
            ")
        );

        if ($withDetails) {
            $itemsDetails = \mint\getItemsDetails(
                array_column(
                    $entries,
                    'item_id'
                ),
                false
            );

            foreach ($entries as $key => $entry) {
                $entries[$key] += $itemsDetails[ $entry['item_id'] ];
            }
        }

        $transactionsItems = \mint\getArraySplitByColumn($entries, 'item_transaction_id');

        return $transactionsItems;
    } else {
        return [];
    }
}

function getItemTransactionOfferedItems(int $transactionId, bool $withDetails = false): array
{
    return \mint\getItemTransactionsItems([$transactionId], $withDetails, 0)[$transactionId];
}

function getItemTransactionBidItems(int $transactionId, bool $withDetails = false): array
{
    return \mint\getItemTransactionsItems([$transactionId], $withDetails, 1)[$transactionId];
}

function getTransactionAskItemsForUser(int $transactionId, int $bidUserId): ?array
{
    global $db;

    $transactionAskItemTypes = ItemTransactionItemTypes::with($db)->getByColumn(
        'item_transaction_id',
        $transactionId
    );

    if ($transactionAskItemTypes) {
        return \mint\getUserItemsDetailsByItemTypeAndResolvedAmount(
            array_column($transactionAskItemTypes, 'amount', 'item_type_id'),
            $bidUserId
        );
    } else {
        return [];
    }
}

function getActiveItemTransactionIdByItemId(int $itemId): ?int
{
    global $db;

    $query = $db->query("
        SELECT
            iTr.id
            FROM
                " . TABLE_PREFIX . "mint_item_transaction_items iTrI
                INNER JOIN " . TABLE_PREFIX . "mint_item_transactions iTr ON iTrI.item_transaction_id = iTr.id
            WHERE iTrI.item_id = " . (int)$itemId . " AND iTr.active = 1
    ");

    if ($db->num_rows($query) == 1) {
        return $db->fetch_field($query, 'id');
    } else {
        return null;
    }
}

function countItemTransactionsItems(array $transactionIds): array
{
    global $db;

    $counts = array_fill_keys($transactionIds, null);

    if ($transactionIds) {
        $results = \mint\queryResultAsArray(
            $db->query("
                SELECT
                    item_transaction_id, COUNT(*) AS n
                    FROM
                        " . TABLE_PREFIX . "mint_item_transaction_items iTrI
                    WHERE iTrI.item_transaction_id IN (" . \mint\getIntegerCsv($transactionIds) . ")
                    GROUP BY item_transaction_id
            "),
            'item_transaction_id',
            'n'
        );

        $counts = $results + $counts;
    }

    return $counts;
}

// item transaction item types
function getItemTransactionsAskItemTypes(array $transactionIds): array
{
    global $db;

    $transactions = array_fill_keys($transactionIds, []);

    if ($transactionIds) {
        $entries = \mint\queryResultAsArray(
            $db->query("
                SELECT
                    iTrITy.item_transaction_id, iTrITy.amount AS stacked_amount,
                    iTy.title AS item_type_title, iTy.description AS item_type_description, iTy.image AS item_type_image, iTy.stacked AS item_type_stacked, iTy.transferable AS item_type_transferable, iTy.discardable AS item_type_discardable
                    FROM
                        " . TABLE_PREFIX . "mint_item_transaction_item_types iTrITy
                        INNER JOIN " . TABLE_PREFIX . "mint_item_types iTy ON iTrITy.item_type_id = iTy.id
                    WHERE iTrITy.item_transaction_id IN (" . \mint\getIntegerCsv($transactionIds) . ")
            ")
        );

        $entries = \mint\getArraySplitByColumn($entries, 'item_transaction_id');

        $transactions = $entries + $transactions;
    }

    return $transactions;
}

function getItemTransactionAskItemTypes(int $transactionId): array
{
    return \mint\getItemTransactionsAskItemTypes([$transactionId])[$transactionId] ?? [];
}
