# Low NoSpam 3

Low NoSpam 3 for ExpressionEngine removes third-party support, but offers a much better API for third-party developers to tap into. Other add-ons can use the Low NoSpam library for their own input.

## Extension Settings

Extension Settings are mostly the same: choose a service, enter an API key (which will be validated upon save), and choose which of the native actions you want the Low NoSpam service to check.

## Low NoSpam Library

The Low NoSpam Library will be loaded on Sessions Start, so it will be available throughout EE using `$this->EE->low_nospam`. The library stores an internal data array with data to send to the NoSpam service. Available methods:

### get_services()

A nested array of available services. Currently Akismet and TypePad AntiSpam.

### set_service($name, $key)

Tells the library which service to use, with which API key. Possible values for `$name` are the keys given in `get_services`.

### key_is_valid()

Returns `TRUE` if the set API is valid.

### get_service()

Currently selected service (array).

### get_service_name()

Currently selected service short name (string).

### set_server_ignore($key, $force)

Add keys to the internal server_ignore array. Each of those keys of the `$_SERVER` superglobal will not be sent along to the service. If a string is given, it will be added to the array. If an array is given, the whole array is added. If the `$force` flag is set to `TRUE`, the whole array will be replaced.

### set_member_groups($ids)

Used to set member group IDs that were set by the user in the Extension Settings.

### get_member_groups()

Returns the member group IDs set by `set_member_groups($ids)`.

### is_available()

Returns `TRUE` if the service is available, `FALSE` if not.

### set_data($key, $value = FALSE)

Either sets a single value in the internal data array or (if `$key` is an array) merges an entire array with the internal data array.

### set_content_by_post($ignore = array())

Sets the content key of the internal data array to the contents in `$_POST`, ignoring the keys given in `$ignore`.