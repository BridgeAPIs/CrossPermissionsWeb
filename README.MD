Web permissions API
===================

** How to use ?

Initialize a `PermissionsBridgeAPI` instance, passing it a `php-redis::Redis()` connected instance.
To get a user, use the `getUser($uuid)` method. 
To get a group, use the `getGroup($groupName)` method.

** Methods Reference

*** PermissionEntity (common for Groups and Users)

`getParents()` : returns an array of [ladder:group], sorted by lader

`getPermissions()` : returns an array of [permission name:boolean]

`getOwnPermissions()` : returns an array of [permission name:boolean] including only entity's self permissions (excluding inherited permissions)

`getProperties()` : returns an array of [property name:value]

`getOwnProperties()` : returns an array of [property name:value] including only entity's self properties (excluding inherited properties)

*** PermissionUser

`isGroupTemporary($group)` : returns true if this group is temporary

`getGroupEnd($group)̀` : returns the end time of the group

*** PermissionGroup

`getGroupName()` : returns the name of the group

`getLadder()` : returns the ladder of the group

