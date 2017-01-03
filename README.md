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
