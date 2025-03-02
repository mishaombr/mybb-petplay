<?php

// Plugin information
$l['petplay_plugin_description'] = 'A pet collection and management system for MyBB forums, inspired by Pokémon mechanics.';
$l['petplay_admin_pluginlibrary_missing'] = 'PluginLibrary is missing. Please install it to use PetPlay.';

// Admin menu
$l['petplay_admin_menu_petplay_title'] = 'PetPlay';

// Species management - Section headers
$l['petplay_admin_species'] = 'Species Management';
$l['petplay_admin_species_list'] = 'Species List';
$l['petplay_admin_species_list_description'] = 'View and manage all pet species.';
$l['petplay_admin_species_add'] = 'Add Species';
$l['petplay_admin_species_add_description'] = 'Add a new pet species to the system.';
$l['petplay_admin_species_edit'] = 'Edit Species';

// Species management - Form fields
$l['petplay_admin_species_id'] = 'ID';
$l['petplay_admin_species_name'] = 'Name';
$l['petplay_admin_species_name_description'] = 'Enter the name of the species.';
$l['petplay_admin_species_description'] = 'Description';
$l['petplay_admin_species_description_desc'] = 'Enter a description for this species.';
$l['petplay_admin_species_type'] = 'Type';
$l['petplay_admin_species_type_description'] = 'Select up to two types for this species.';

// Species management - Sprites
$l['petplay_admin_species_sprite'] = 'Normal Sprite';
$l['petplay_admin_species_sprite_description'] = 'Upload the normal sprite for this species (recommended size: 96x96px).';
$l['petplay_admin_species_shiny_sprite'] = 'Shiny Sprite';
$l['petplay_admin_species_shiny_sprite_desc'] = 'Upload the shiny sprite for this species (recommended size: 96x96px).';
$l['petplay_admin_species_mini_sprite'] = 'Mini Sprite';
$l['petplay_admin_species_mini_sprite_description'] = 'Upload the mini sprite for this species (recommended size: 32x32px).';

// Species management - Actions and messages
$l['petplay_admin_species_empty'] = 'No species found.';
$l['petplay_admin_species_add_button'] = 'Add New Species';
$l['petplay_admin_species_submit'] = 'Submit';
$l['petplay_admin_species_added'] = 'Species has been added successfully.';
$l['petplay_admin_species_updated'] = 'Species has been updated successfully.';
$l['petplay_admin_species_invalid'] = 'Invalid species specified.';
$l['petplay_admin_species_deleted'] = 'Species has been deleted successfully.';
$l['petplay_admin_species_delete_confirm_title'] = 'Delete Species';
$l['petplay_admin_species_delete_confirm_message'] = 'Are you sure you want to delete this species?';

// Type management - Section headers
$l['petplay_admin_types'] = 'Type Management';
$l['petplay_admin_types_list'] = 'Types List';
$l['petplay_admin_types_list_description'] = 'View and manage all pet types.';
$l['petplay_admin_types_add'] = 'Add Type';
$l['petplay_admin_types_add_description'] = 'Add a new pet type to the system.';
$l['petplay_admin_types_edit'] = 'Edit Type';

// Type management - Form fields
$l['petplay_admin_types_id'] = 'ID';
$l['petplay_admin_types_name'] = 'Name';
$l['petplay_admin_types_name_description'] = 'Enter the name of the type.';
$l['petplay_admin_types_description'] = 'Description';
$l['petplay_admin_types_description_desc'] = 'Enter a thematic description for this type.';
$l['petplay_admin_types_colour'] = 'Colour';
$l['petplay_admin_types_colour_description'] = 'Choose a colour for this type. This will be used for UI elements and styling.';
$l['petplay_admin_types_is_default'] = 'Default Type';
$l['petplay_admin_types_is_default_description'] = 'Set as the default type for new species.';

// Type management - Actions and messages
$l['petplay_admin_types_empty'] = 'No types found.';
$l['petplay_admin_types_add_button'] = 'Add New Type';
$l['petplay_admin_types_submit'] = 'Submit';
$l['petplay_admin_types_added'] = 'Type has been added successfully.';
$l['petplay_admin_types_updated'] = 'Type has been updated successfully.';
$l['petplay_admin_types_invalid'] = 'Invalid type specified.';
$l['petplay_admin_types_deleted'] = 'Type has been deleted successfully.';
$l['petplay_admin_types_delete_confirm_title'] = 'Delete Type';
$l['petplay_admin_types_delete_confirm_message'] = 'Are you sure you want to delete this type? If any species only has this type, they will be assigned the default type instead.';
$l['petplay_admin_types_default_exists'] = 'There must be at least one default type. Please set another type as default before changing this one.';
$l['petplay_admin_types_in_use'] = 'This type is in use by one or more species.';

// Add these after the type management section

// Nature management - Section headers
$l['petplay_admin_natures'] = 'Nature Management';
$l['petplay_admin_natures_list'] = 'Natures List';
$l['petplay_admin_natures_list_description'] = 'View and manage all pet natures.';
$l['petplay_admin_natures_add'] = 'Add Nature';
$l['petplay_admin_natures_add_description'] = 'Add a new pet nature to the system.';
$l['petplay_admin_natures_edit'] = 'Edit Nature';

// Nature management - Form fields
$l['petplay_admin_natures_id'] = 'ID';
$l['petplay_admin_natures_name'] = 'Name';
$l['petplay_admin_natures_name_description'] = 'Enter the name of the nature.';
$l['petplay_admin_natures_description'] = 'Description';
$l['petplay_admin_natures_description_desc'] = 'Enter a description for this nature.';
$l['petplay_admin_natures_increased_stat'] = 'Increased Stat';
$l['petplay_admin_natures_increased_stat_desc'] = 'Select which stat this nature increases.';
$l['petplay_admin_natures_decreased_stat'] = 'Decreased Stat';
$l['petplay_admin_natures_decreased_stat_desc'] = 'Select which stat this nature decreases.';
$l['petplay_admin_natures_is_default'] = 'Default Nature';
$l['petplay_admin_natures_is_default_description'] = 'Set as the default nature for new pets.';

// Nature management - Actions and messages
$l['petplay_admin_natures_empty'] = 'No natures found.';
$l['petplay_admin_natures_add_button'] = 'Add New Nature';
$l['petplay_admin_natures_submit'] = 'Submit';
$l['petplay_admin_natures_added'] = 'Nature has been added successfully.';
$l['petplay_admin_natures_updated'] = 'Nature has been updated successfully.';
$l['petplay_admin_natures_invalid'] = 'Invalid nature specified.';
$l['petplay_admin_natures_deleted'] = 'Nature has been deleted successfully.';
$l['petplay_admin_natures_delete_confirm_title'] = 'Delete Nature';
$l['petplay_admin_natures_delete_confirm_message'] = 'Are you sure you want to delete this nature?';
$l['petplay_admin_natures_default_exists'] = 'There must be at least one default nature. Please set another nature as default before changing this one.';
$l['petplay_admin_natures_in_use'] = 'This nature is in use by one or more pets.';

// Nature management - Validation messages
$l['petplay_admin_natures_no_name'] = 'You must enter a name for the nature.';
$l['petplay_admin_natures_no_increased_stat'] = 'You must select which stat this nature increases.';
$l['petplay_admin_natures_no_decreased_stat'] = 'You must select which stat this nature decreases.';

// Pet management - Section headers
$l['petplay_admin_pets_list'] = 'Pets List';
$l['petplay_admin_pets_list_description'] = 'View and manage all pets in the system.';
$l['petplay_admin_pets_add'] = 'Add Pet';
$l['petplay_admin_pets_add_description'] = 'Add a new pet to the system.';
$l['petplay_admin_pets_edit'] = 'Edit Pet';

// Pet management - List columns
$l['petplay_admin_pets_id'] = 'ID';
$l['petplay_admin_pets_nickname'] = 'Nickname';
$l['petplay_admin_pets_species'] = 'Species';
$l['petplay_admin_pets_original_owner'] = 'Original Owner';
$l['petplay_admin_pets_current_owner'] = 'Current Owner';
$l['petplay_admin_pets_attributes'] = 'Attributes';

// Pet management - Form fields
$l['petplay_admin_pets_nickname_desc'] = 'Enter an optional nickname for the pet.';
$l['petplay_admin_pets_species_desc'] = 'Select the species of the pet.';
$l['petplay_admin_pets_owner_desc'] = 'Enter the MyBB user ID of the original owner.';
$l['petplay_admin_pets_owner_username'] = 'Start typing username...';
$l['petplay_admin_pets_gender'] = 'Gender';
$l['petplay_admin_pets_gender_desc'] = 'Select the gender of the pet.';
$l['petplay_admin_pets_gender_none'] = 'None';
$l['petplay_admin_pets_gender_male'] = 'Male';
$l['petplay_admin_pets_gender_female'] = 'Female';
$l['petplay_admin_pets_is_shiny'] = 'Shiny';
$l['petplay_admin_pets_is_shiny_desc'] = 'Check if this pet is shiny.';
$l['petplay_admin_pets_nature'] = 'Nature';
$l['petplay_admin_pets_nature_desc'] = 'Select the nature of the pet.';
$l['petplay_admin_pets_ability'] = 'Ability';
$l['petplay_admin_pets_ability_desc'] = 'Enter the ability of the pet.';
$l['petplay_admin_pets_is_fainted'] = 'Fainted';
$l['petplay_admin_pets_is_fainted_desc'] = 'Check if this pet is currently fainted.';

// Pet management - Status labels
$l['petplay_admin_pets_shiny'] = 'Shiny';
$l['petplay_admin_pets_fainted'] = 'Fainted';

// Pet management - Actions and messages
$l['petplay_admin_pets_empty'] = 'No pets found.';
$l['petplay_admin_pets_add_button'] = 'Add New Pet';
$l['petplay_admin_pets_submit'] = 'Submit';
$l['petplay_admin_pets_added'] = 'Pet has been added successfully.';
$l['petplay_admin_pets_updated'] = 'Pet has been updated successfully.';
$l['petplay_admin_pets_invalid'] = 'Invalid pet specified.';
$l['petplay_admin_pets_deleted'] = 'Pet has been deleted successfully.';
$l['petplay_admin_pets_delete_confirm_title'] = 'Delete Pet';
$l['petplay_admin_pets_delete_confirm_message'] = 'Are you sure you want to delete this pet? This will also remove all ownership history.';

// Pet management - Validation messages
$l['petplay_admin_pets_no_species'] = 'You must select a species for the pet.';
$l['petplay_admin_pets_invalid_species'] = 'The selected species is invalid.';
$l['petplay_admin_pets_no_owner'] = 'You must specify an owner for the pet.';
$l['petplay_admin_pets_invalid_owner'] = 'The specified owner is invalid.';

// Add these after the pet management section
$l['petplay_admin_pets_history'] = 'History';
$l['petplay_admin_pets_history_title'] = 'Pet Ownership History';
$l['petplay_admin_pets_history_description'] = 'View the complete ownership history for this pet.';
$l['petplay_admin_pets_history_owner'] = 'Owner';
$l['petplay_admin_pets_history_acquired'] = 'Acquired';
$l['petplay_admin_pets_history_current'] = 'Current Owner';
$l['petplay_admin_pets_history_empty'] = 'No ownership history found for this pet.';

$l['petplay_admin_pets_transfer'] = 'Transfer';
$l['petplay_admin_pets_transfer_title'] = 'Transfer Pet';
$l['petplay_admin_pets_transfer_description'] = 'Transfer this pet to a new owner.';
$l['petplay_admin_pets_new_owner'] = 'New Owner';
$l['petplay_admin_pets_new_owner_desc'] = 'Enter the MyBB user ID of the new owner.';
$l['petplay_admin_pets_transferred'] = 'Pet has been transferred successfully.';
$l['petplay_admin_pets_transfer_error'] = 'Error transferring pet ownership.';
$l['petplay_admin_pets_transfer_confirm_message'] = 'Are you sure you want to transfer this pet to a new owner?';
$l['petplay_admin_pets_already_owner'] = 'This user is already the current owner of this pet.';

$l['petplay_admin_species_base_stats'] = "Base Stats";
$l['petplay_admin_species_stat_hp'] = "HP";
$l['petplay_admin_species_stat_attack'] = "Attack";
$l['petplay_admin_species_stat_defence'] = "Defence";
$l['petplay_admin_species_stat_special_attack'] = "Special Attack";
$l['petplay_admin_species_stat_special_defence'] = "Special Defence";
$l['petplay_admin_species_stat_speed'] = "Speed";
$l['petplay_admin_species_stat_description'] = "Enter the base {1} stat for this species (1-255)";

$l['petplay_admin_pets_ivs'] = "Individual Values";
$l['petplay_admin_pets_iv_hp'] = "HP IV";
$l['petplay_admin_pets_iv_attack'] = "Attack IV";
$l['petplay_admin_pets_iv_defence'] = "Defence IV";
$l['petplay_admin_pets_iv_special_attack'] = "Special Attack IV";
$l['petplay_admin_pets_iv_special_defence'] = "Special Defence IV";
$l['petplay_admin_pets_iv_speed'] = "Speed IV";
$l['petplay_admin_pets_iv_description'] = "Enter a value between 0 and 31";
$l['petplay_admin_pets_iv_invalid'] = "The {1} value must be between 0 and 31";
$l['petplay_admin_pets_iv_randomize'] = "Randomize IVs";

$l['petplay_admin_pets_add_error'] = "An error occurred while adding the pet. Please try again.";
