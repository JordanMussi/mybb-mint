<?php

// plugins
$l['mint_description'] = 'Adds virtual currency mined through user activity and marketplace for virtual items.';

$l['mint_admin_pluginlibrary_missing'] = 'Mint requires <a href="https://github.com/frostschutz/MyBB-PluginLibrary">PluginLibrary</a> in order to be installed.';

// common
$l['mint_admin_submit'] = 'Submit';

// Users
$l['mint_inventory_type'] = 'Inventory Type';
$l['mint_inventory_slots_bonus'] = 'Bonus Inventory Slots';
$l['mint_reward_multiplier'] = 'Content Entity Reward Multiplier';
$l['mint_reward_multiplier_description'] = 'Users\' rewards will be multiplied by this number (rounded to nearest integer). Set to "1" to leave reward amounts unaffected.';
$l['mint_active_item_transactions_limit'] = 'Active Item Transactions Limit';
$l['mint_active_item_transactions_limit_description'] = 'Maximum number of user\'s active Item Transactions. Set to "0" to allow creating Transactions without restrictions.';

// Mint
$l['mint_admin'] = 'Mint';

// Mint: Item Categories
$l['mint_admin_item_categories'] = 'Item Categories';
$l['mint_admin_item_categories_page_description'] = 'Here you can manage item categories.';
$l['mint_admin_item_categories_list'] = 'Item Categories ({1})';
$l['mint_admin_item_categories_empty'] = 'No item categories added.';

$l['mint_admin_item_categories_id'] = 'ID';
$l['mint_admin_item_categories_title'] = 'Title';
$l['mint_admin_item_categories_title_description'] = 'Title that will be used within the interface.';
$l['mint_admin_item_categories_image'] = 'Image';
$l['mint_admin_item_categories_image_description'] = 'Path to an image representing the item category. Paths relative to the forum root directory are supported.';

$l['mint_admin_item_categories_add'] = 'Add Item Category';
$l['mint_admin_item_categories_update'] = 'Update Item Category';

$l['mint_admin_item_categories_added'] = 'Successfully added item category.';
$l['mint_admin_item_categories_updated'] = 'Successfully updated item category.';
$l['mint_admin_item_categories_deleted'] = 'Successfully deleted item category.';
$l['mint_admin_item_categories_delete_confirm_title'] = 'Confirm item category removal';
$l['mint_admin_item_categories_delete_confirm_message'] = 'Are you sure you want to remove selected item category?';

// Mint: Item Types
$l['mint_admin_item_types'] = 'Item Types';
$l['mint_admin_item_types_page_description'] = 'Here you can manage item types.';
$l['mint_admin_item_types_list'] = 'Item Types ({1})';
$l['mint_admin_item_types_empty'] = 'No item types added.';
$l['mint_admin_item_types_interactions_registered'] = 'Registered interactions';
$l['mint_admin_item_types_interactions_registered_list'] = 'Registered interactions: {1}';

$l['mint_admin_item_types_id'] = 'ID';
$l['mint_admin_item_types_name'] = 'Name';
$l['mint_admin_item_types_name_description'] = 'Choose an internal identifier that may be used by custom modules.';
$l['mint_admin_item_types_title'] = 'Title';
$l['mint_admin_item_types_title_description'] = 'Title that will be used within the interface.';
$l['mint_admin_item_types_description'] = 'Description';
$l['mint_admin_item_types_description_description'] = 'Supports MyCode.';
$l['mint_admin_item_types_item_category_id'] = 'Item Category';
$l['mint_admin_item_types_item_category_id_description'] = '';
$l['mint_admin_item_types_item_category'] = 'Category';
$l['mint_admin_item_types_item_category_description'] = '';
$l['mint_admin_item_types_image'] = 'Image';
$l['mint_admin_item_types_image_description'] = 'Path to an image representing the item type. Paths relative to the forum root directory are supported.';
$l['mint_admin_item_types_stacked'] = 'Stacked';
$l['mint_admin_item_types_stacked_description'] = 'Choose whether items of this type should only take one slot in users\' inventories.';
$l['mint_admin_item_types_transferable'] = 'Transferable';
$l['mint_admin_item_types_transferable_description'] = 'Choose whether users can arrange transactions including items of this type.';
$l['mint_admin_item_types_discardable'] = 'Discardable';
$l['mint_admin_item_types_discardable_description'] = 'Choose whether users can abandon items of this type on their own.';
$l['mint_admin_item_types_referenceable'] = 'Referenceable';
$l['mint_admin_item_types_referenceable_description'] = 'Choose whether the item type should be included in the Item Reference.';

$l['mint_admin_item_types_add'] = 'Add Item Type';
$l['mint_admin_item_types_update'] = 'Update Item Type';

$l['mint_admin_item_types_added'] = 'Successfully added item type.';
$l['mint_admin_item_types_updated'] = 'Successfully updated item type.';
$l['mint_admin_item_types_deleted'] = 'Successfully deleted item type.';
$l['mint_admin_item_types_delete_confirm_title'] = 'Confirm item type removal';
$l['mint_admin_item_types_delete_confirm_message'] = 'Are you sure you want to remove selected item type?';
$l['mint_admin_item_types_error_item_category_invalid'] = 'Selected Item Category is invalid.';

// Mint: Inventory Types
$l['mint_admin_inventory_types'] = 'Inventory Types';
$l['mint_admin_inventory_types_page_description'] = 'Here you can manage inventory types.';
$l['mint_admin_inventory_types_list'] = 'Inventory Types ({1})';
$l['mint_admin_inventory_types_empty'] = 'No inventory types added.';

$l['mint_admin_inventory_types_id'] = 'ID';
$l['mint_admin_inventory_types_slots'] = 'Slots';
$l['mint_admin_inventory_types_slots_description'] = '';
$l['mint_admin_inventory_types_title'] = 'Title';
$l['mint_admin_inventory_types_title_description'] = '';

$l['mint_admin_inventory_types_add'] = 'Add Inventory Type';
$l['mint_admin_inventory_types_added'] = 'Successfully added inventory type.';
$l['mint_admin_inventory_types_update'] = 'Update Inventory Type';
$l['mint_admin_inventory_types_updated'] = 'Successfully updated inventory type.';
$l['mint_admin_inventory_types_deleted'] = 'Successfully deleted inventory type.';
$l['mint_admin_inventory_types_delete_confirm_title'] = 'Confirm inventory type removal';
$l['mint_admin_inventory_types_delete_confirm_message'] = 'Are you sure you want to remove selected inventory type?';

// Mint Logs
$l['mint_admin_logs'] = 'Mint Logs';

// Mint Logs: Balance Operations
$l['mint_admin_balance_operations'] = 'Balance Operations';
$l['mint_admin_balance_operations_page_description'] = 'This section allows you to view a history of users\' balance operations.';
$l['mint_admin_balance_operations_list'] = 'Balance Operations ({1})';
$l['mint_admin_balance_operations_empty'] = 'No balance operations to show.';
$l['mint_admin_balance_operations_filter'] = 'Filters';

$l['mint_admin_balance_operations_id'] = 'ID';
$l['mint_admin_balance_operations_date'] = 'Date';
$l['mint_admin_balance_operations_user'] = 'User';
$l['mint_admin_balance_operations_user_description'] = '';
$l['mint_admin_balance_operations_value'] = 'Value';
$l['mint_admin_balance_operations_value_description'] = '';
$l['mint_admin_balance_operations_result_balance'] = 'Result Balance';
$l['mint_admin_balance_operations_result_balance_description'] = '';
$l['mint_admin_balance_operations_balance_transfer_id'] = 'Transfer';
$l['mint_admin_balance_operations_balance_transfer_id_description'] = 'ID of a Balance Transfer.';
$l['mint_admin_balance_operations_termination_point'] = 'Termination Point';
$l['mint_admin_balance_operations_termination_point_description'] = 'Name of a Termination Point.';

// Mint Logs: Balance Transfers
$l['mint_admin_balance_transfers'] = 'Balance Transfers';
$l['mint_admin_balance_transfers_page_description'] = 'This section allows you to view a history of users\' balance transfers.';
$l['mint_admin_balance_transfers_list'] = 'Balance Transfers ({1})';
$l['mint_admin_balance_transfers_empty'] = 'No balance transfers to show.';
$l['mint_admin_balance_transfers_filter'] = 'Filters';

$l['mint_admin_balance_transfers_id'] = 'ID';
$l['mint_admin_balance_transfers_from_user'] = 'From User';
$l['mint_admin_balance_transfers_from_user_description'] = '';
$l['mint_admin_balance_transfers_to_user'] = 'To User';
$l['mint_admin_balance_transfers_to_user_description'] = '';
$l['mint_admin_balance_transfers_value'] = 'Value';
$l['mint_admin_balance_transfers_value_description'] = '';
$l['mint_admin_balance_transfers_date'] = 'Date';
$l['mint_admin_balance_transfers_note'] = 'Note';
$l['mint_admin_balance_transfers_private'] = 'Private';
$l['mint_admin_balance_transfers_private_description'] = '';

// Mint Logs: Item Transactions
$l['mint_admin_item_transactions'] = 'Item Transactions';
$l['mint_admin_item_transactions_page_description'] = 'This section allows you to view a history users\' item transactions.';
$l['mint_admin_item_transactions_list'] = 'Item Transactions ({1})';
$l['mint_admin_item_transactions_empty'] = 'No item transactions to show.';
$l['mint_admin_item_transactions_filter'] = 'Filters';

$l['mint_admin_item_transactions_id'] = 'ID';
$l['mint_admin_item_transactions_ask_date'] = 'Creation Date';
$l['mint_admin_item_transactions_ask_date_description'] = '';
$l['mint_admin_item_transactions_ask_user'] = 'From';
$l['mint_admin_item_transactions_ask_user_description'] = '';
$l['mint_admin_item_transactions_bid_user'] = 'To';
$l['mint_admin_item_transactions_bid_user_description'] = '';
$l['mint_admin_item_transactions_ask_price'] = 'Ask Price';
$l['mint_admin_item_transactions_ask_price_description'] = '';
$l['mint_admin_item_transactions_active'] = 'Active';
$l['mint_admin_item_transactions_active_description'] = '';
$l['mint_admin_item_transactions_completed'] = 'Completed';
$l['mint_admin_item_transactions_completed_description'] = '';
$l['mint_admin_item_transactions_completed_date'] = 'Completion Date';
$l['mint_admin_item_transactions_completed_date_description'] = '';
$l['mint_admin_item_transactions_balance_transfer_id'] = 'Balance Transfer';
$l['mint_admin_item_transactions_balance_transfer_id_description'] = 'ID of a Balance Transfer.';

// tasks
$l['mint_integrity_task_ran'] = 'The Mint: Integrity Check task successfully ran.';
