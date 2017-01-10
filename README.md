Mentions plugin
================
 * Replaces @username with links to the user's profile
 * Sends notifications to users mentioned in posts

## Notes

### Supported content types

To add support for custom object or annotation types in outgoing notifications,
add a corresponding language key pair to your language file:

``mentions:notification_types:object:<object_type>``
``mentions:notification_types:annotation:<annotation_name>``

### Object fields scanned for mentions

Use `'get_fields','mentions'` hook to expand the scope of scanned fields
beyond object `title` and `description`. The hook receives `entity` and expects
and array of fields in return.

### Replacement of mentions with anchors

To add a view which should be scanned for @mentions and replaced with an anchor,
use `'get_views', 'mentions'` hook.