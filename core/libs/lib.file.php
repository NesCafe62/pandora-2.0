<?php
namespace core\libs;

use Exception;
use debug;

class file {

	public static function delete($filename) {
		if (!is_file($filename)) {
			return false;
		}
		return unlink($filename);
	}

	public static function getExtension($filename) {
		return pathinfo($filename, PATHINFO_EXTENSION);
	}

	public static function getPath($filename) {
		return pathinfo($filename, PATHINFO_DIRNAME);
	}

	public static function getFilename($filename) {
		return pathinfo($filename, PATHINFO_BASENAME);
	}

	public static function safeFilename($filename) {
		$filename = preg_replace('#[\\\\/:\*\?"<>\|\0]+#', '', $filename);
		if ($filename === '.' || $filename === '..' || $filename === '') {
			$filename = '_';
		}
		return $filename;
	}

	/* public static function getFiles($path, $mask = '*', $sort = false) {
		;
	} */

	/* public static function getFolders($path, $sort) {
		;
	} */

	public const FILE_TYPES_NONE = 0;
	public const FILE_TYPES_FILE = 1;
	public const FILE_TYPES_FOLDER = 2;
	public const FILE_TYPES_ALL = 3;

	public static function search($path, $types = self::FILE_TYPES_ALL, $recursive = false, $mask = '*') {
		if ($types == self::FILE_TYPES_NONE) {
			trigger_error(debug::_('FILE_SEARCH_FILE_TYPES_NONE'), E_USER_WARNING);
			return [];
		}
		$path = trimRight($path, '/');
		$flags = GLOB_NOSORT;
		if ($types == self::FILE_TYPES_FOLDER) {
			$flags |= GLOB_ONLYDIR;
		}
		$items = glob($path.'/'.$mask, $flags);
		if ($types == self::FILE_TYPES_FILE) {
			$files = [];
			foreach ($items as $item) {
				if (!is_dir($item)) {
					$files[] = $item;
				}
			}
			$items = $files;
		}

		if ($recursive && ($types & self::FILE_TYPES_FOLDER != 0)) {
			$result = [];
			foreach ($items as $item) {
				$result[] = $item;
				if (is_dir($item)) {
					$result = array_merge($result, self::search($item, $types, true, $mask));
				}
			}
		} else {
			$result = $items;
		}

		return $result;
	}

	public static function createPath($path) {
		if (is_dir($path)) {
			return true;
		}
		$res = mkdir($path, 0777, true);
		if (!$res) {
			trigger_error(debug::_('FILE_CREATE_PATH_ERROR', $path), E_USER_WARNING);
		}
		return $res;
	}

	public static function upload($file, $filename, $overwrite = false) {
		if (!isset($file['tmp_name'])) {
			trigger_error(debug::_('FILES_UPLOAD_TMP_FILE_NOT_UPLOADED', $file['name'] ?? '', $filename),E_USER_WARNING);
			return false;
		}
		$path = self::getPath($filename);
		if (!self::createPath($path)) {
			trigger_error(debug::_('FILES_UPLOAD_PATH_CREATE_FAILED', $path),E_USER_WARNING);
			return false;
		}

		if (is_file($filename)) {
			if ($overwrite) {
				unlink($filename);
			} else {
				trigger_error(debug::_('FILES_UPLOAD_FILE_ALREADY_EXIST', $filename),debug::WARNING);
				return false;
			}
		}
		return move_uploaded_file($file['tmp_name'], $filename);
	}

}