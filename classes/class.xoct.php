<?php
/* Copyright (c) 1998-2009 ILIAS open source, Extended GPL, see docs/LICENSE */
require_once __DIR__ . '/../vendor/autoload.php';
require_once('./include/inc.ilias_version.php');
/**
 * Class xoct
 *
 * @author  Theodor Truffer <tt@studer-raimann.ch>
 */
class xoct {

	const ILIAS_50 = 50;
	const ILIAS_51 = 51;
	const ILIAS_52 = 52;
	const ILIAS_53 = 53;
	const ILIAS_54 = 54;
	const ILIAS_60 = 60;
	const MIN_ILIAS_VERSION = self::ILIAS_53;

	/**
	 * @return int
	 */
	public static function getILIASVersion() {
		if (strpos(ILIAS_VERSION_NUMERIC, 'alpha') || strpos(ILIAS_VERSION_NUMERIC, 'beta')
			|| ilComponent::isVersionGreaterString(ILIAS_VERSION_NUMERIC, '5.4.999')) {
			return self::ILIAS_60;
		}
		if (ilComponent::isVersionGreaterString(ILIAS_VERSION_NUMERIC, '5.3.999')) {
			return self::ILIAS_54;
		}
		if (ilComponent::isVersionGreaterString(ILIAS_VERSION_NUMERIC, '5.2.999')) {
			return self::ILIAS_53;
		}
		if (ilComponent::isVersionGreaterString(ILIAS_VERSION_NUMERIC, '5.1.999')) {
			return self::ILIAS_52;
		}
		if (ilComponent::isVersionGreaterString(ILIAS_VERSION_NUMERIC, '5.0.999')) {
			return self::ILIAS_51;
		}
		if (ilComponent::isVersionGreaterString(ILIAS_VERSION_NUMERIC, '4.9.999')) {
			return self::ILIAS_50;
		}

		return 0;
	}

	/**
	 * @return bool
	 */
	public static function isIlias50() {
		return self::getILIASVersion() >= self::ILIAS_50;
	}

	/**
	 * @return bool
	 */
	public static function isIlias51() {
		return self::getILIASVersion() >= self::ILIAS_51;
	}

	/**
	 * @return bool
	 */
	public static function isIlias52() {
		return self::getILIASVersion() >= self::ILIAS_52;
	}

	/**
	 * @return bool
	 */
	public static function isIlias53() {
		return self::getILIASVersion() >= self::ILIAS_53;
	}

	/**
	 * @return bool
	 */
	public static function isIlias54() {
		return self::getILIASVersion() >= self::ILIAS_54;
	}

	public static function isApiVersionGreaterThan($api_version) {
		return version_compare(xoctConf::getConfig(xoctConf::F_API_VERSION), $api_version, '>=');
	}

	/**
	 *
	 */
	public static function isApi11() {
		return self::isApiVersionGreaterThan('v1.1.0');
	}

	/**
	 *
	 */
	public static function initILIAS() {
		chdir(self::getRootPath());
		require_once('./Services/Context/classes/class.ilContext.php');
		require_once('./Services/Authentication/classes/class.ilAuthFactory.php');
		$il_context_auth = ilAuthFactory::CONTEXT_WEB;
		$_COOKIE['ilClientId'] = $_SERVER['argv'][3];
		$_POST['username'] = $_SERVER['argv'][1];
		$_POST['password'] = $_SERVER['argv'][2];

		ilAuthFactory::setContext($il_context_auth);
		require_once('./include/inc.header.php');
	}

	/**
	 * @return string
	 */
	public static function getRootPath() {
		//		$override_file = dirname(__FILE__) . '/Configuration/root';
		//		if (is_file($override_file)) {
		//			$path = file_get_contents($override_file);
		//			$path = rtrim($path, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
		//
		//			return $path;
		//		}

		$path = realpath(dirname(__FILE__) . '/../../../../../../../..');
		$path = rtrim($path, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;

		return $path;
	}

}