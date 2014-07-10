<?php namespace Cartalyst\Sentinel\Roles;
/**
 * Part of the Sentinel package.
 *
 * NOTICE OF LICENSE
 *
 * Licensed under the Cartalyst PSL License.
 *
 * This source file is subject to the Cartalyst PSL License that is
 * bundled with this package in the license.txt file.
 *
 * @package    Sentinel
 * @version    1.0.0
 * @author     Cartalyst LLC
 * @license    Cartalyst PSL
 * @copyright  (c) 2011-2014, Cartalyst LLC
 * @link       http://cartalyst.com
 */

use Cartalyst\Sentinel\Permissions\PermissibleInterface;
use Cartalyst\Sentinel\Permissions\SentinelPermissions;
use Illuminate\Database\Eloquent\Model;

class EloquentRole extends Model implements RoleInterface, PermissibleInterface {

	/**
	 * {@inheritDoc}
	 */
	protected $table = 'roles';

	/**
	 * {@inheritDoc}
	 */
	protected $fillable = [
		'slug',
		'name',
		'permissions',
	];

	/**
	 * The users model name.
	 *
	 * @var string
	 */
	protected static $usersModel = 'Cartalyst\Sentinel\Users\EloquentUser';

	/**
	 * Users relationship.
	 *
	 * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
	 */
	public function users()
	{
		return $this->belongsToMany(static::$usersModel, 'roles_users', 'role_id', 'user_id')->withTimestamps();
	}

	/**
	 * Get mutator for the "permissions" attribute.
	 *
	 * @param  mixed  $permissions
	 * @return array
	 */
	public function getPermissionsAttribute($permissions)
	{
		return $permissions ? json_decode($permissions, true) : [];
	}

	/**
	 * Set mutator for the "permissions" attribute.
	 *
	 * @param  mixed  $permissions
	 * @return void
	 */
	public function setPermissionsAttribute(array $permissions)
	{
		$this->attributes['permissions'] = $permissions ? json_encode($permissions) : '';
	}

	/**
	 * {@inheritDoc}
	 */
	public function getRoleId()
	{
		return $this->getKey();
	}

	/**
	 * {@inheritDoc}
	 */
	public function getRoleSlug()
	{
		return $this->slug;
	}

	/**
	 * {@inheritDoc}
	 */
	public function getUsers()
	{
		return $this->users;
	}

	/**
	 * {@inheritDoc}
	 */
	public function getPermissions()
	{
		if ($this->permissionsInstance === null)
		{
			$this->permissionsInstance = $this->createPermissions();
		}

		return $this->permissionsInstance;
	}

	/**
	 * Creates a permissions object.
	 *
	 * @return \Cartalyst\Sentinel\Permissions\PermissionsInterface
	 */
	protected function createPermissions()
	{
		return new SentinelPermissions($this->permissions);
	}

	/**
	 * {@inheritDoc}
	 */
	public function addPermission($permission, $value)
	{
		if ( ! array_key_exists($permission, $this->permissions))
		{
			$this->permissions = array_merge($this->permissions, [$permission => $value]);
		}

		return $this;
	}

	/**
	 * {@inheritDoc}
	 */
	public function updatePermission($permission, $value)
	{
		if (array_key_exists($permission, $this->permissions))
		{
			$permissions = $this->permissions;

			$permissions[$permission] = $value;

			$this->permissions = $permissions;
		}

		return $this;
	}

	/**
	 * {@inheritDoc}
	 */
	public function removePermission($permission)
	{
		if (array_key_exists($permission, $this->permissions))
		{
			$permissions = $this->permissions;

			unset($permissions[$permission]);

			$this->permissions = $permissions;
		}

		return $this;
	}

	/**
	 * Get the users model.
	 *
	 * @return string
	 */
	public static function getUsersModel()
	{
		return static::$usersModel;
	}

	/**
	 * Set the users model.
	 *
	 * @param  string  $usersModel
	 * @return void
	 */
	public static function setUsersModel($usersModel)
	{
		static::$usersModel = $usersModel;
	}

	/**
	 * Dynamically pass missing methods to the role.
	 *
	 * @param  string  $method
	 * @param  array   $parameters
	 * @return mixed
	 */
	public function __call($method, $parameters)
	{
		$methods = ['hasAccess', 'hasAnyAccess'];

		if (in_array($method, $methods))
		{
			$permissions = $this->getPermissions();

			return call_user_func_array([$permissions, $method], $parameters);
		}

		return parent::__call($method, $parameters);
	}

}
