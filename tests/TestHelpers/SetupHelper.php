<?php
/**
 * ownCloud
 *
 * @author Artur Neumann <artur@jankaritech.com>
 * @copyright Copyright (c) 2017 Artur Neumann artur@jankaritech.com
 *
 * This code is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License,
 * as published by the Free Software Foundation;
 * either version 3 of the License, or any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>
 *
 */
namespace TestHelpers;

use Behat\Testwork\Hook\Scope\HookScope;
use GuzzleHttp\Exception\ServerException;
use Exception;
use Psr\Http\Message\ResponseInterface;
use SimpleXMLElement;

/**
 * Helper to setup UI / Integration tests
 *
 * @author Artur Neumann <artur@jankaritech.com>
 *
 */
class SetupHelper extends \PHPUnit\Framework\Assert {

	/**
	 * @var string
	 */
	private static $ocPath = null;
	/**
	 * @var string
	 */
	private static $baseUrl = null;
	/**
	 * @var string
	 */
	private static $adminUsername = null;
	/**
	 * @var string
	 */
	private static $adminPassword = null;

	/**
	 * creates a user
	 *
	 * @param string $userName
	 * @param string $password
	 * @param string $xRequestId
	 * @param string $displayName
	 * @param string $email
	 *
	 * @return string[] associated array with "code", "stdOut", "stdErr"
	 */
	public static function createUser(
		$userName,
		$password,
		$xRequestId = '',
		$displayName = null,
		$email = null
	) {
		$occCommand = ['user:add', '--password-from-env'];
		if ($displayName !== null) {
			$occCommand = \array_merge($occCommand, ["--display-name", $displayName]);
		}
		if ($email !== null) {
			$occCommand = \array_merge($occCommand, ["--email", $email]);
		}
		\putenv("OC_PASS=" . $password);
		return self::runOcc(
			\array_merge($occCommand, [$userName]),
			$xRequestId
		);
	}

	/**
	 * deletes a user
	 *
	 * @param string $userName
	 * @param string $xRequestId
	 *
	 * @return string[] associated array with "code", "stdOut", "stdErr"
	 */
	public static function deleteUser(
		$userName,
		$xRequestId = ''
	) {
		return self::runOcc(
			['user:delete', $userName],
			$xRequestId
		);
	}

	/**
	 *
	 * @param string $userName
	 * @param string $app
	 * @param string $key
	 * @param string $value
	 * @param string $xRequestId
	 *
	 * @return string[]
	 */
	public static function changeUserSetting(
		$userName,
		$app,
		$key,
		$value,
		$xRequestId = ''
	) {
		return self::runOcc(
			['user:setting', '--value ' . $value, $userName, $app, $key],
			$xRequestId
		);
	}

	/**
	 * creates a group
	 *
	 * @param string $groupName
	 * @param string $xRequestId
	 *
	 * @return string[] associated array with "code", "stdOut", "stdErr"
	 */
	public static function createGroup(
		$groupName,
		$xRequestId = ''
	) {
		return self::runOcc(
			['group:add', $groupName],
			$xRequestId
		);
	}

	/**
	 * adds an existing user to a group, creating the group if it does not exist
	 *
	 * @param string $groupName
	 * @param string $userName
	 * @param string $xRequestId
	 *
	 * @return string[] associated array with "code", "stdOut", "stdErr"
	 */
	public static function addUserToGroup(
		$groupName,
		$userName,
		$xRequestId = ''
	) {
		return self::runOcc(
			['group:add-member', '--member', $userName, $groupName],
			$xRequestId
		);
	}

	/**
	 * removes a user from a group
	 *
	 * @param string $groupName
	 * @param string $userName
	 * @param string $xRequestId
	 *
	 * @return string[] associated array with "code", "stdOut", "stdErr"
	 */
	public static function removeUserFromGroup(
		$groupName,
		$userName,
		$xRequestId = ''
	) {
		return self::runOcc(
			['group:remove-member', '--member', $userName, $groupName],
			$xRequestId
		);
	}

	/**
	 * deletes a group
	 *
	 * @param string $groupName
	 * @param string $xRequestId
	 *
	 * @return string[] associated array with "code", "stdOut", "stdErr"
	 */
	public static function deleteGroup(
		$groupName,
		$xRequestId = ''
	) {
		return self::runOcc(
			['group:delete', $groupName],
			$xRequestId
		);
	}

	/**
	 *
	 * @param string $xRequestId
	 *
	 * @return string[]
	 */
	public static function getGroups(
		$xRequestId = ''
	) {
		return \json_decode(
			self::runOcc(
				['group:list', '--output=json'],
				$xRequestId
			)['stdOut']
		);
	}
	/**
	 *
	 * @param HookScope $scope
	 *
	 * @return array of suite context parameters
	 */
	public static function getSuiteParameters(HookScope $scope) {
		return $scope->getEnvironment()->getSuite()
			->getSettings() ['context'] ['parameters'];
	}

	/**
	 * Fixup OC path so that it always starts with a "/" and does not end with
	 * a "/".
	 *
	 * @param string $ocPath
	 *
	 * @return string
	 */
	private static function normaliseOcPath($ocPath) {
		return '/' . \trim($ocPath, '/');
	}

	/**
	 *
	 * @param string $adminUsername
	 * @param string $adminPassword
	 * @param string $baseUrl
	 * @param string $ocPath
	 *
	 * @return void
	 */
	public static function init(
		$adminUsername,
		$adminPassword,
		$baseUrl,
		$ocPath
	) {
		foreach (\func_get_args() as $variableToCheck) {
			if (!\is_string($variableToCheck)) {
				throw new \InvalidArgumentException(
					"mandatory argument missing or wrong type ($variableToCheck => "
					. \gettype($variableToCheck) . ")"
				);
			}
		}
		self::$adminUsername = $adminUsername;
		self::$adminPassword = $adminPassword;
		self::$baseUrl = \rtrim($baseUrl, '/');
		self::$ocPath = self::normaliseOcPath($ocPath);
	}

	/**
	 *
	 * @return string path to the testing app occ endpoint
	 * @throws Exception if ocPath has not been set yet
	 */
	public static function getOcPath() {
		if (self::$ocPath === null) {
			throw new Exception(
				"getOcPath called before ocPath is set by init"
			);
		}

		return self::$ocPath;
	}

	/**
	 *
	 * @param string $baseUrl
	 * @param string $adminUsername
	 * @param string $adminPassword
	 * @param string $xRequestId
	 *
	 * @return SimpleXMLElement
	 * @throws Exception
	 */
	public static function getSysInfo(
		$baseUrl,
		$adminUsername,
		$adminPassword,
		$xRequestId = ''
	) {
		$result = OcsApiHelper::sendRequest(
			$baseUrl,
			$adminUsername,
			$adminPassword,
			"GET",
			"/apps/testing/api/v1/sysinfo",
			$xRequestId
		);
		if ($result->getStatusCode() !== 200) {
			throw new \Exception(
				"could not get sysinfo " . $result->getReasonPhrase()
			);
		}
		return HttpRequestHelper::getResponseXml($result, __METHOD__)->data;
	}

	/**
	 *
	 * @param string $baseUrl
	 * @param string $adminUsername
	 * @param string $adminPassword
	 * @param string $xRequestId
	 *
	 * @return string
	 * @throws Exception
	 */
	public static function getServerRoot(
		$baseUrl,
		$adminUsername,
		$adminPassword,
		$xRequestId = ''
	) {
		$sysInfo = self::getSysInfo(
			$baseUrl,
			$adminUsername,
			$adminPassword,
			$xRequestId
		);
		// server_root is a SimpleXMLElement object that "wraps" a string.
		/// We want the bare string.
		return (string) $sysInfo->server_root;
	}

	/**
	 * @param string|null $adminUsername
	 * @param string $callerName
	 *
	 * @return string
	 * @throws Exception
	 */
	private static function checkAdminUsername($adminUsername, $callerName) {
		if (self::$adminUsername === null
			&& $adminUsername === null
		) {
			throw new Exception(
				"$callerName called without adminUsername - pass the username or call SetupHelper::init"
			);
		}
		if ($adminUsername === null) {
			$adminUsername = self::$adminUsername;
		}
		return $adminUsername;
	}

	/**
	 * @param string|null $adminPassword
	 * @param string $callerName
	 *
	 * @return string
	 * @throws Exception
	 */
	private static function checkAdminPassword($adminPassword, $callerName) {
		if (self::$adminPassword === null
			&& $adminPassword === null
		) {
			throw new Exception(
				"$callerName called without adminPassword - pass the password or call SetupHelper::init"
			);
		}
		if ($adminPassword === null) {
			$adminPassword = self::$adminPassword;
		}
		return $adminPassword;
	}

	/**
	 * @param string|null $baseUrl
	 * @param string $callerName
	 *
	 * @return string
	 * @throws Exception
	 */
	private static function checkBaseUrl($baseUrl, $callerName) {
		if (self::$baseUrl === null
			&& $baseUrl === null
		) {
			throw new Exception(
				"$callerName called without baseUrl - pass the baseUrl or call SetupHelper::init"
			);
		}
		if ($baseUrl === null) {
			$baseUrl = self::$baseUrl;
		}
		return $baseUrl;
	}

	/**
	 *
	 * @param string $dirPathFromServerRoot e.g. 'apps2/myapp/appinfo'
	 * @param string $xRequestId
	 * @param string|null $baseUrl
	 * @param string|null $adminUsername
	 * @param string|null $adminPassword
	 *
	 * @return void
	 * @throws Exception
	 */
	public static function mkDirOnServer(
		$dirPathFromServerRoot,
		$xRequestId = '',
		$baseUrl = null,
		$adminUsername = null,
		$adminPassword = null
	) {
		$baseUrl = self::checkBaseUrl($baseUrl, "mkDirOnServer");
		$adminUsername = self::checkAdminUsername($adminUsername, "mkDirOnServer");
		$adminPassword = self::checkAdminPassword($adminPassword, "mkDirOnServer");
		$result = OcsApiHelper::sendRequest(
			$baseUrl,
			$adminUsername,
			$adminPassword,
			"POST",
			"/apps/testing/api/v1/dir",
			$xRequestId,
			['dir' => $dirPathFromServerRoot]
		);

		if ($result->getStatusCode() !== 200) {
			throw new \Exception(
				"could not create directory $dirPathFromServerRoot " . $result->getReasonPhrase()
			);
		}
	}

	/**
	 *
	 * @param string $dirPathFromServerRoot e.g. 'apps2/myapp/appinfo'
	 * @param string $xRequestId
	 * @param string|null $baseUrl
	 * @param string|null $adminUsername
	 * @param string|null $adminPassword
	 *
	 * @return void
	 * @throws Exception
	 */
	public static function rmDirOnServer(
		$dirPathFromServerRoot,
		$xRequestId = '',
		$baseUrl = null,
		$adminUsername = null,
		$adminPassword = null
	) {
		$baseUrl = self::checkBaseUrl($baseUrl, "rmDirOnServer");
		$adminUsername = self::checkAdminUsername($adminUsername, "rmDirOnServer");
		$adminPassword = self::checkAdminPassword($adminPassword, "rmDirOnServer");
		$result = OcsApiHelper::sendRequest(
			$baseUrl,
			$adminUsername,
			$adminPassword,
			"DELETE",
			"/apps/testing/api/v1/dir",
			$xRequestId,
			['dir' => $dirPathFromServerRoot]
		);

		if ($result->getStatusCode() !== 200) {
			throw new \Exception(
				"could not delete directory $dirPathFromServerRoot " . $result->getReasonPhrase()
			);
		}
	}

	/**
	 *
	 * @param string $filePathFromServerRoot e.g. 'app2/myapp/appinfo/info.xml'
	 * @param string $content
	 * @param string $xRequestId
	 * @param string|null $baseUrl
	 * @param string|null $adminUsername
	 * @param string|null $adminPassword
	 *
	 * @return void
	 * @throws Exception
	 */
	public static function createFileOnServer(
		$filePathFromServerRoot,
		$content,
		$xRequestId = '',
		$baseUrl = null,
		$adminUsername = null,
		$adminPassword = null
	) {
		$baseUrl = self::checkBaseUrl($baseUrl, "createFileOnServer");
		$adminUsername = self::checkAdminUsername($adminUsername, "createFileOnServer");
		$adminPassword = self::checkAdminPassword($adminPassword, "createFileOnServer");
		$result = OcsApiHelper::sendRequest(
			$baseUrl,
			$adminUsername,
			$adminPassword,
			"POST",
			"/apps/testing/api/v1/file",
			$xRequestId,
			[
				'file' => $filePathFromServerRoot,
				'content' => $content
			]
		);

		if ($result->getStatusCode() !== 200) {
			throw new \Exception(
				"could not create file $filePathFromServerRoot " . $result->getReasonPhrase()
			);
		}
	}

	/**
	 *
	 * @param string $filePathFromServerRoot e.g. 'app2/myapp/appinfo/info.xml'
	 * @param string $xRequestId
	 * @param string|null $baseUrl
	 * @param string|null $adminUsername
	 * @param string|null $adminPassword
	 *
	 * @return void
	 * @throws Exception
	 */
	public static function deleteFileOnServer(
		$filePathFromServerRoot,
		$xRequestId = '',
		$baseUrl = null,
		$adminUsername = null,
		$adminPassword = null
	) {
		$baseUrl = self::checkBaseUrl($baseUrl, "deleteFileOnServer");
		$adminUsername = self::checkAdminUsername($adminUsername, "deleteFileOnServer");
		$adminPassword = self::checkAdminPassword($adminPassword, "deleteFileOnServer");
		$result = OcsApiHelper::sendRequest(
			$baseUrl,
			$adminUsername,
			$adminPassword,
			"DELETE",
			"/apps/testing/api/v1/file",
			$xRequestId,
			[
				'file' => $filePathFromServerRoot
			]
		);

		if ($result->getStatusCode() !== 200) {
			throw new \Exception(
				"could not delete file $filePathFromServerRoot " . $result->getReasonPhrase()
			);
		}
	}

	/**
	 * @param string $fileInCore e.g. 'app2/myapp/appinfo/info.xml'
	 * @param string $xRequestId
	 * @param string|null $baseUrl
	 * @param string|null $adminUsername
	 * @param string|null $adminPassword
	 *
	 * @return string
	 * @throws Exception
	 */
	public static function readFileFromServer(
		$fileInCore,
		$xRequestId = '',
		$baseUrl  = null,
		$adminUsername = null,
		$adminPassword = null
	) {
		$baseUrl = self::checkBaseUrl($baseUrl, "readFile");
		$adminUsername = self::checkAdminUsername(
			$adminUsername,
			"readFile"
		);
		$adminPassword = self::checkAdminPassword(
			$adminPassword,
			"readFile"
		);

		$response = OcsApiHelper::sendRequest(
			$baseUrl,
			$adminUsername,
			$adminPassword,
			'GET',
			"/apps/testing/api/v1/file?file={$fileInCore}",
			$xRequestId
		);
		self::assertSame(
			200,
			$response->getStatusCode(),
			"Failed to read the file {$fileInCore}"
		);
		$localContent = HttpRequestHelper::getResponseXml($response, __METHOD__);
		$localContent = (string)$localContent->data->element->contentUrlEncoded;
		return \urldecode($localContent);
	}

	/**
	 * returns the content of a file in a skeleton folder
	 *
	 * @param string $fileInSkeletonFolder
	 * @param string $xRequestId
	 * @param string|null $baseUrl
	 * @param string|null $adminUsername
	 * @param string|null $adminPassword
	 *
	 * @return string content of the file
	 */
	public static function readSkeletonFile(
		$fileInSkeletonFolder,
		$xRequestId = '',
		$baseUrl = null,
		$adminUsername = null,
		$adminPassword = null
	) {
		$baseUrl = self::checkBaseUrl($baseUrl, "readSkeletonFile");
		$adminUsername = self::checkAdminUsername(
			$adminUsername,
			"readSkeletonFile"
		);
		$adminPassword = self::checkAdminPassword(
			$adminPassword,
			"readSkeletonFile"
		);

		//find the absolute path of the currently set skeletondirectory
		$occResponse = self::runOcc(
			['config:system:get', 'skeletondirectory'],
			$xRequestId,
			$adminUsername,
			$adminPassword,
			$baseUrl
		);
		if ((int) $occResponse['code'] !== 0) {
			throw new \Exception(
				"could not get current skeletondirectory. " . $occResponse['stdErr']
			);
		}
		$skeletonRoot = \trim($occResponse['stdOut']);

		$fileInSkeletonFolder = \rawurlencode("$skeletonRoot/$fileInSkeletonFolder");
		$response = OcsApiHelper::sendRequest(
			$baseUrl,
			$adminUsername,
			$adminPassword,
			'GET',
			"/apps/testing/api/v1/file?file={$fileInSkeletonFolder}&absolute=true",
			$xRequestId
		);
		self::assertSame(
			200,
			$response->getStatusCode(),
			"Failed to read the file {$fileInSkeletonFolder}"
		);
		$localContent = HttpRequestHelper::getResponseXml($response, __METHOD__);
		$localContent = (string)$localContent->data->element->contentUrlEncoded;
		$localContent = \urldecode($localContent);
		return $localContent;
	}

	/**
	 * enables an app
	 *
	 * @param string $appName
	 * @param string $xRequestId
	 *
	 * @return string[] associated array with "code", "stdOut", "stdErr"
	 */
	public static function enableApp(
		$appName,
		$xRequestId = ''
	) {
		return self::runOcc(
			['app:enable', $appName],
			$xRequestId
		);
	}

	/**
	 * disables an app
	 *
	 * @param string $appName
	 * @param string $xRequestId
	 *
	 * @return string[] associated array with "code", "stdOut", "stdErr"
	 */
	public static function disableApp(
		$appName,
		$xRequestId = ''
	) {
		return self::runOcc(
			['app:disable', $appName],
			$xRequestId
		);
	}

	/**
	 * checks if an app is currently enabled
	 *
	 * @param string $appName
	 * @param string $xRequestId
	 *
	 * @return bool true if enabled, false if disabled or nonexistent
	 */
	public static function isAppEnabled(
		$appName,
		$xRequestId = ''
	) {
		$result = self::runOcc(
			['app:list', '^' . $appName . '$'],
			$xRequestId
		);
		return \strtolower(\substr($result['stdOut'], 0, 7)) === 'enabled';
	}

	/**
	 * lists app status (enabled or disabled)
	 *
	 * @param string $appName
	 * @param string $xRequestId
	 *
	 * @return bool true if the app needed to be enabled, false otherwise
	 */
	public static function enableAppIfNotEnabled(
		$appName,
		$xRequestId = ''
	) {
		if (!self::isAppEnabled($appName, $xRequestId)) {
			self::enableApp(
				$appName,
				$xRequestId
			);
			return true;
		}

		return false;
	}

	/**
	 * Runs a list of occ commands at once
	 *
	 * @param Array $commands
	 * @param string $xRequestId
	 * @param string|null $adminUsername
	 * @param string|null $adminPassword
	 * @param string|null $baseUrl
	 *
	 * @return SimpleXMLElement
	 */
	public static function runBulkOcc(
		$commands,
		$xRequestId = '',
		$adminUsername = null,
		$adminPassword = null,
		$baseUrl = null
	) {
		if (OcisHelper::isTestingOnOcisOrReva()) {
			return [];
		}
		$baseUrl = self::checkBaseUrl($baseUrl, "runOcc");
		$adminUsername = self::checkAdminUsername($adminUsername, "runOcc");
		$adminPassword = self::checkAdminPassword($adminPassword, "runOcc");

		if (!\is_array($commands)) {
			throw new Exception("commands must be an array");
		}

		$isTestingAppEnabledText = "Is the testing app installed and enabled?\n";
		$bodies = [];

		foreach ($commands as $occ) {
			if (!\array_key_exists('command', $occ)) {
				throw new \InvalidArgumentException("command key is missing in array passed to runBulkOcc");
			}

			$body = [
				'command' => \implode(' ', $occ['command'])
			];

			if (isset($occ['envVariables'])) {
				$body['env_variables'] = $occ['envVariables'];
			}
			\array_push($bodies, $body);
		}
		try {
			$result = OcsApiHelper::sendRequest(
				$baseUrl,
				$adminUsername,
				$adminPassword,
				"POST",
				"/apps/testing/api/v1/occ/bulk?format=json",
				$xRequestId,
				\json_encode($bodies)
			);
		} catch (ServerException $e) {
			throw new Exception(
				"Could not execute 'occ'. " .
				$isTestingAppEnabledText .
				$e->getResponse()->getBody()
			);
		}
		$result = \json_decode($result->getBody()->getContents());

		return $result->ocs->data;
	}

	/**
	 * invokes an OCC command
	 *
	 * @param array $args anything behind "occ".
	 *                    For example: "files:transfer-ownership"
	 * @param string $xRequestId
	 * @param string|null $adminUsername
	 * @param string|null $adminPassword
	 * @param string|null $baseUrl
	 * @param string|null $ocPath
	 * @param array|null $envVariables
	 *
	 * @return string[] associated array with "code", "stdOut", "stdErr"
	 * @throws Exception if parameters have not been provided yet or the testing app is not enabled
	 */
	public static function runOcc(
		$args,
		$xRequestId = '',
		$adminUsername = null,
		$adminPassword = null,
		$baseUrl = null,
		$ocPath = null,
		$envVariables = null
	) {
		if (OcisHelper::isTestingOnOcisOrReva()) {
			return ['code' => '', 'stdOut' => '', 'stdErr' => '' ];
		}
		$baseUrl = self::checkBaseUrl($baseUrl, "runOcc");
		$adminUsername = self::checkAdminUsername($adminUsername, "runOcc");
		$adminPassword = self::checkAdminPassword($adminPassword, "runOcc");
		if (self::$ocPath === null
			&& $ocPath === null
		) {
			throw new Exception(
				"runOcc called without ocPath - pass the ocPath or call SetupHelper::init"
			);
		}
		if ($ocPath === null) {
			$ocPath = self::$ocPath;
		} else {
			$ocPath = self::normaliseOcPath($ocPath);
		}

		$body = [];
		$body['command'] = \implode(' ', $args);

		if ($envVariables !== null) {
			$body['env_variables'] = $envVariables;
		}

		$isTestingAppEnabledText = "Is the testing app installed and enabled?\n";

		try {
			$result = OcsApiHelper::sendRequest(
				$baseUrl,
				$adminUsername,
				$adminPassword,
				"POST",
				$ocPath,
				$xRequestId,
				$body
			);
		} catch (ServerException $e) {
			throw new Exception(
				"Could not execute 'occ'. " .
				$isTestingAppEnabledText .
				$e->getResponse()->getBody()
			);
		}

		$return = [];
		$contents = $result->getBody()->getContents();
		$resultXml = \simplexml_load_string($contents);

		if ($resultXml === false) {
			throw new Exception(
				"Response is not valid XML after executing 'occ $args'. " .
				$isTestingAppEnabledText .
				$contents
			);
		}

		$return['code'] = $resultXml->xpath("//ocs/data/code");
		$return['stdOut'] = $resultXml->xpath("//ocs/data/stdOut");
		$return['stdErr'] = $resultXml->xpath("//ocs/data/stdErr");

		if (!isset($return['code'][0])) {
			throw new Exception(
				"Return code not found after executing 'occ $args'. " .
				$isTestingAppEnabledText .
				$contents
			);
		}

		if (!isset($return['stdOut'][0])) {
			throw new Exception(
				"Return stdOut not found after executing 'occ $args'. " .
				$isTestingAppEnabledText .
				$contents
			);
		}

		if (!isset($return['stdErr'][0])) {
			throw new Exception(
				"Return stdErr not found after executing 'occ $args'. " .
				$isTestingAppEnabledText .
				$contents
			);
		}

		if (!\is_a($return['code'][0], "SimpleXMLElement")) {
			throw new Exception(
				"Return code is not a SimpleXMLElement after executing 'occ $args'. " .
				$isTestingAppEnabledText .
				$contents
			);
		}

		if (!\is_a($return['stdOut'][0], "SimpleXMLElement")) {
			throw new Exception(
				"Return stdOut is not a SimpleXMLElement after executing 'occ $args'. " .
				$isTestingAppEnabledText .
				$contents
			);
		}

		if (!\is_a($return['stdErr'][0], "SimpleXMLElement")) {
			throw new Exception(
				"Return stdErr is not a SimpleXMLElement after executing 'occ $args'. " .
				$isTestingAppEnabledText .
				$contents
			);
		}

		$return['code'] = $return['code'][0]->__toString();
		$return['stdOut'] = $return['stdOut'][0]->__toString();
		$return['stdErr'] = $return['stdErr'][0]->__toString();
		self::resetOpcache(
			$baseUrl,
			$adminUsername,
			$adminPassword,
			$xRequestId
		);
		return $return;
	}

	/**
	 * @param string $baseUrl
	 * @param string $user
	 * @param string $password
	 * @param string $xRequestId
	 *
	 * @return ResponseInterface
	 */
	public static function resetOpcache(
		$baseUrl,
		$user,
		$password,
		$xRequestId = ''
	) {
		try {
			return OcsApiHelper::sendRequest(
				$baseUrl,
				$user,
				$password,
				"DELETE",
				"/apps/testing/api/v1/opcache",
				$xRequestId
			);
		} catch (ServerException $e) {
			echo "could not reset opcache, if tests fail try to set " .
				"'opcache.revalidate_freq=0' in the php.ini file\n";
		}
	}

	/**
	 * Create local storage mount
	 *
	 * @param string $mount (name of local storage mount)
	 * @param string $xRequestId
	 *
	 * @return string[] associated array with "code", "stdOut", "stdErr", "storageId"
	 */
	public static function createLocalStorageMount(
		$mount,
		$xRequestId = ''
	) {
		$mountPath = TEMPORARY_STORAGE_DIR_ON_REMOTE_SERVER . "/$mount";
		SetupHelper::mkDirOnServer(
			$mountPath,
			$xRequestId
		);
		// files_external:create requires absolute path
		$serverRoot = self::getServerRoot(
			self::$baseUrl,
			self::$adminUsername,
			self::$adminPassword,
			$xRequestId
		);
		$result = self::runOcc(
			[
				'files_external:create',
				$mount,
				'local',
				'null::null',
				'-c',
				'datadir=' . $serverRoot . '/' . $mountPath
			],
			$xRequestId
		);
		// stdOut should have a string like "Storage created with id 65"
		$storageIdWords = \explode(" ", \trim($result['stdOut']));
		$result['storageId'] = (int)$storageIdWords[4];
		return $result;
	}

	/**
	 * Get a system config setting, including status code, output and standard
	 * error output.
	 *
	 * @param string $key
	 * @param string $xRequestId
	 * @param string|null $output e.g. json
	 * @param string|null $adminUsername
	 * @param string|null $adminPassword
	 * @param string|null $baseUrl
	 * @param string|null $ocPath
	 *
	 * @return string[] associated array with "code", "stdOut", "stdErr"
	 * @throws Exception if parameters have not been provided yet or the testing app is not enabled
	 */
	public static function getSystemConfig(
		$key,
		$xRequestId = '',
		$output = null,
		$adminUsername = null,
		$adminPassword = null,
		$baseUrl = null,
		$ocPath = null
	) {
		$args = [];
		$args[] = 'config:system:get';
		$args[] = $key;

		if ($output !== null) {
			$args[] = '--output';
			$args[] = $output;
		}

		$args[] = '--no-ansi';

		return self::runOcc(
			$args,
			$xRequestId,
			$adminUsername,
			$adminPassword,
			$baseUrl,
			$ocPath
		);
	}

	/**
	 * Set a system config setting
	 *
	 * @param string $key
	 * @param string $value
	 * @param string $xRequestId
	 * @param string|null $type e.g. boolean or json
	 * @param string|null $output e.g. json
	 * @param string|null $adminUsername
	 * @param string|null $adminPassword
	 * @param string|null $baseUrl
	 * @param string|null $ocPath
	 *
	 * @return string[] associated array with "code", "stdOut", "stdErr"
	 * @throws Exception if parameters have not been provided yet or the testing app is not enabled
	 */
	public static function setSystemConfig(
		$key,
		$value,
		$xRequestId = '',
		$type = null,
		$output = null,
		$adminUsername = null,
		$adminPassword = null,
		$baseUrl = null,
		$ocPath = null
	) {
		$args = [];
		$args[] = 'config:system:set';
		$args[] = $key;
		$args[] = '--value';
		$args[] = $value;

		if ($type !== null) {
			$args[] = '--type';
			$args[] = $type;
		}

		if ($output !== null) {
			$args[] = '--output';
			$args[] = $output;
		}

		$args[] = '--no-ansi';
		if ($baseUrl === null) {
			$baseUrl = self::$baseUrl;
		}
		return self::runOcc(
			$args,
			$xRequestId,
			$adminUsername,
			$adminPassword,
			$baseUrl,
			$ocPath
		);
	}

	/**
	 * Get the value of a system config setting
	 *
	 * @param string $key
	 * @param string $xRequestId
	 * @param string|null $output e.g. json
	 * @param string|null $adminUsername
	 * @param string|null $adminPassword
	 * @param string|null $baseUrl
	 * @param string|null $ocPath
	 *
	 * @return string
	 * @throws Exception if parameters have not been provided yet or the testing app is not enabled
	 */
	public static function getSystemConfigValue(
		$key,
		$xRequestId = '',
		$output = null,
		$adminUsername = null,
		$adminPassword = null,
		$baseUrl = null,
		$ocPath = null
	) {
		if ($baseUrl === null) {
			$baseUrl = self::$baseUrl;
		}
		return self::getSystemConfig(
			$key,
			$xRequestId,
			$output,
			$adminUsername,
			$adminPassword,
			$baseUrl,
			$ocPath
		)['stdOut'];
	}

	/**
	 * Finds all lines containing the given text
	 *
	 * @param string $input stdout or stderr output
	 * @param string $text text to search for
	 *
	 * @return array array of lines that matched
	 */
	public static function findLines($input, $text) {
		$results = [];
		foreach (\explode("\n", $input) as $line) {
			if (\strpos($line, $text) !== false) {
				$results[] = $line;
			}
		}
		return $results;
	}

	/**
	 * Delete a system config setting
	 *
	 * @param string $key
	 * @param string $xRequestId
	 * @param string|null $adminUsername
	 * @param string|null $adminPassword
	 * @param string|null $baseUrl
	 * @param string|null $ocPath
	 *
	 * @return string[] associated array with "code", "stdOut", "stdErr"
	 * @throws Exception if parameters have not been provided yet or the testing app is not enabled
	 */
	public static function deleteSystemConfig(
		$key,
		$xRequestId = '',
		$adminUsername = null,
		$adminPassword = null,
		$baseUrl = null,
		$ocPath = null
	) {
		$args = [];
		$args[] = 'config:system:delete';
		$args[] = $key;

		$args[] = '--no-ansi';
		if ($baseUrl === null) {
			$baseUrl = self::$baseUrl;
		}
		return SetupHelper::runOcc(
			$args,
			$xRequestId,
			$adminUsername,
			$adminPassword,
			$baseUrl,
			$ocPath
		);
	}
}
