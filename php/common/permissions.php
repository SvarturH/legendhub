<?php
$root = realpath(getenv("DOCUMENT_ROOT"));
require_once("$root/php/common/config.php");

class Permissions {
	static $Authenticated;
	static $PermissionList;
	static $PDO;

	public static function Check($permission = null, $mustCreate = null, $mustRead = null, $mustUpdate = null, $mustDelete = null) {
		self::InitPDO();

		if (!self::CheckLogin()) {
			return False;
		}

		if (!self::CheckPermissions($permission, $mustCreate, $mustRead, $mustUpdate, $mustDelete)) {
			return False;
		}

		return True;
	}

	private static function InitPDO() {
		if (!isset(self::$PDO)) {
			self::$PDO = getPDO();
		}
	}

	private static function CheckLogin() {
		if (!isset(self::$Authenticated)) {
			if (isset($_SESSION['UserId'])) {
				$query = self::$PDO->prepare("SELECT Banned FROM Members WHERE Id = :id");
        			$query->execute(array("id" => $_SESSION['UserId']));
        			if ($res = $query->fetch(PDO::FETCH_OBJ)) {
						if ($res->Banned) {
								session_unset();
								session_destroy();

								self::$Authenticated = False;
						}
						else {
							self::$Authenticated = True;
						}
        			}
			}
			else {
        			self::$Authenticated = False;
			}
		}

		return self::$Authenticated;
	}

	private static function CheckPermissions($permission, $mustCreate, $mustRead, $mustUpdate, $mustDelete) {
		if (!isset(self::$PermissionList)) {
			self::GatherPermissions();
		}

		if (!is_null($permission)) {

			//check permission
			$canCreate = False;
			$canRead = False;
			$canUpdate = False;
			$canDelete = False;
			
			foreach (self::$PermissionList as $perm) {
				if ($perm->Name == $permission) {
					$canCreate |= $perm->Create;
					$canRead |= $perm->Read;
					$canUpdate |= $perm->Update;
					$canDelete |= $perm->Delete;
				}
			}

			return !(($mustCreate && !$canCreate) ||
			    ($mustRead && !$canRead) ||
			    ($mustUpdate && !$canUpdate) ||
			    ($mustDelete && !$canDelete));
		}
		else {
			return True;
		}
	}

	private static function GatherPermissions() {
		if (!isset(self::$PermissionList)) {
			$query = self::$PDO->prepare("SELECT DISTINCT P.Name, RPM.Create, RPM.Read, RPM.Update, RPM.Delete
						FROM Members M
							JOIN MemberRoleMap MRM ON MRM.MemberId = M.Id
							JOIN RolePermissionMap RPM ON RPM.RoleId = MRM.RoleId
							JOIN Permissions P ON P.Id = RPM.PermissionId
						WHERE M.Id = :memberId");
        		$query->execute(array("memberId" => $_SESSION['UserId']));
			self::$PermissionList = $query->fetchAll(PDO::FETCH_OBJ);
		}
	}
}
?>
