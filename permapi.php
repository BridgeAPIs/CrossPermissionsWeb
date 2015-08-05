<?php

class PermissionEntity {
	protected $parents;
	protected $entityId;
	protected $permissions;
	protected $properties;

	public __construct($parents, $entityId, $permissions, $properties) {
		$this->parents = $parents;
		$this->entityId = $entityId;
		$this->permissions = $permissions;
		$this->properties = $properties;
	}

	public getParents() {
		ksort($this->parents);
		return $this->parents;
	}

	public getPermissions() {
		$ret = array();
		for ($this->getParents() as $group) {
			for ($group->getPermissions() as $perm => $val)
				$ret[$perm] = $val;
		}

		for ($permissions as $perm => $val)
			$ret[$perm] = $val;

		return $ret;
	}

	public getOwnPermissions() {
		$ret = array();
		for ($permissions as $perm => $val)
			$ret[$perm] = $val;

		return $ret;
	}

	public getProperties() {
		$ret = array();
		for ($this->getParents() as $group) {
			for ($group->getProperties() as $property => $val)
				$ret[$property] = $val;
		}

		for ($properties as $property => $val)
			$ret[$property] = $val;

		return $ret;
	}

	public getEntityProperties() {
		$ret = array();
		for ($properties as $property => $val)
			$ret[$property] = $val;

		return $ret;
	}

	public getEntityId() {
		return $this->entityId;
	}
}

class PermissionUser extends PermissionEntity {
	public __construct($parents, $entityId, $permissions, $properties) {
		parent::__construct($parents, $entityId, $permissions, $properties);
	}

	public isGroupTemporary(String $group) {
		return isset($this->getEntityProperties()[$group."-until"]);
	}

	public getGroupEnd(String $group) {
		if (!isset($this->getEntityProperties()[$group."-until"]))
			return 0;
		$end = $this->getEntityProperties()[$group."-until"];
		return intval($end);
	}
}

class PermissionGroup extends PermissionEntity {
	private $ladder;
	private $groupName;

	public __construct($parents, $entityId, $permissions, $properties, $ladder, $groupName) {
		parent::__construct($parents, $entityId, $permissions, $properties);
		$this->ladder = $ladder;
		$this->groupName = $groupName;
	}

	public getGroupName() {
		return $this->groupName;
	}

	public getLadder() {
		return $this->ladder;
	}
}

class PermissionsBridgeAPI {
	private $phpRedis;
	private $groups; // Internal cache, to avoir running multiple calls for the same user
	private $users; // Internal cache, to avoid running multiple calls for the same user

	public __construct($phpRedis) {
		$this->phpRedis = $phpRedis;
	}

	public getUser($userId) {
		if (isset($users[$userId]))
			return $users[$userId];

		$redis = $this->phpRedis;
		$prefix = "user:" . $userId . ":";
		$dbparents = $redis->lRange($prefix . "parents", 0, - 1);
		$options = $redis->hGetAll($prefix . "options");
		$dbperms = $redis->hGetAll($prefix . "perms");

		if ($dbparents == null)
			$dbparents = array();
		if ($options == null)
			$options = array();
		if ($dbperms == null)
			$dbperms = array();

		$parents = array();
		for ($dbparents as $parent) {
			$gp = getGroup($parent);
			if ($gp != null)
				$parents[$gp->getLadder()] = $gp;
		}	

		$user = new PermissionUser($parents, $userId, $dbperms, $options);
		$users[$userId] = $user;
		return $user;
	}

	public getGroup($groupName) {
		$idTable = $this->phpRedis->hGet("groups:list", $groupName);
		return getGroup($groupName);
	}

	public getGroup($groupId) {
		if (!isset($groups[$groupId]))
			return loadGroup($groupId)
		return $groups[$groupId];
	}

	private loadGroup($groupId) {
		$redis = $this->phpRedis;
		$prefix = "groups:" . $groupId . ":";
		$dbparents = $redis->lRange($prefix . "parents", 0, - 1);
		$options = $redis->hGetAll($prefix . "options");
		$dbperms = $redis->hGetAll($prefix . "perms");
		$dbladder = $redis->get($prefix . "ladder");
		$dbname = $redis->get($prefix . "name");
				
		if ($dbladder == null) 
			return null;
			
		$ladder = intval($dbladder);
		// Formatage des données
		$parents = array();
		for ($dbparents as $parent) {
			$gp = getGroup($parent);
			if ($gp != null)
				$parents[$gp->getLadder()] = $gp;
		}	

		// Génération de l'objet
		$group = new PermissionsGroup($parents, $groupId, $dbperms, $options, $dbladder, $dbname);
		$groups[$groupId] = $group;
		return $group;
	}
}	