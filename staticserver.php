<?php

/*
	Class:
		StaticServer
	
	Class for serving static files. Caches and compresses CSS-files, should be able
	to minify JS files and cache them, serve images as well.
	
	Well, most of the cacheing and minifying is done by Cowl plugins. See frontcontroller.php.
*/

class StaticServer
{
	// Property: StaticServer::$files_dir
	// The directory where files can be found
	static protected $files_dir;
	
	// Property: StaticServer::$VALID_TYPES
	// To keep the StaticServer from serving bad files (such as php files)
	// the allowed types are defined here. If the request type does not match
	// any types here it should not be served statically
	protected static $MIMES = array(
		'json' => 'text/json',
		'css' => 'text/css',
		'js' => 'application/javascript',
		'jpg' => 'image/jpeg',
		'jpeg' => 'image/jpeg',
		'gif' => 'image/gif',
		'png' => 'image/png',
		'bmp' => 'image/bmp',
		'html' => 'text/html',
		'rss' => 'application/rss+xml',
		'partial' => 'text/html',
		'otf' => 'font/opentype',
		'ttf' => 'font/ttf'
	);

	static protected $BAD = array('php', 'phtml', 'ini', 'sql');
	
	// Property: StaticServer::$path
	// The path to (possible) serve
	protected $path;
	
	// Property: StaticServer::$is_file
	// Is the path tied to a file?
	protected $is_file = false;
	
	// Property: StaticServer::$type
	// The type of the file. (based on the extension)
	protected $type;
	
	// Property: StaticServer::$is_locked
	// If the path has been locked
	protected $is_locked = false;
	
	/*
		Constructor
		
		Parameters:
			(string) $path - The path to server
	*/
	
	public function __construct($path)
	{
		$this->setPath($path);
	}
	
	/*
		Method:
			parsePath
		
		Parse the path and check for a static file.
	*/
	
	private function parsePath()
	{
		if ( empty($this->path) || (! strstr($this->path, 'gfx') && ! strstr($this->path, 'css') && ! strstr($this->path, 'js')) )
		{
			$this->is_file = false;
			return;
		}
		
		// Check to see if it really exists
		if ( file_exists($this->path) )
		{
			$this->is_file = true;
		}
		// Try to translate to the app-directory
		// If it is successful <StaticServer::$path> will be altered
		elseif ( file_exists(self::$files_dir . $this->path) )
		{
			$this->path = self::$files_dir . $this->path;
			$this->is_file = true;
		}
		
		// Get the extension
		$this->type = strtolower(array_last(explode('.', $this->path)));
		
		// Bad filetype!
		if ( in_array($this->type, self::$BAD) )
		{
			$this->is_file = false;
		}
	}
	
	/*
		Method:
			isFile
		
		Check if the current path maps to a static path on disk.
		
		Returns:
			Wether it exists
	*/
	
	public function isFile()
	{
		return $this->is_file;
	}
	
	/*
		Method:
			render
		
		Render the request. The next course of action is to abort the script.
		That is left for the API user to do, so to facilitate clean up and other
		scenarios.
	*/
	
	public function render()
	{
		// Sanity check to see that render hasn't been called when there was no file
		if ( ! $this->is_file )
			return;
		
		header('Cache-Control: private');
		header('Pragma: private');
		
		// This is removed because Chrome won't even send a request if it has an expires headers, thus defeating the HTTP_IF_NONE_MATCH
		//header('Expires: ' . date(DATE_RFC2822, (time() + 60 * 60 * 24 * 365)));
		$mime = isset(self::$MIMES[$this->type]) ? self::$MIMES[$this->type] : 'text/html';
		$mod_time = filemtime($this->path);
		
		header('Content-type: ' . $mime);
		header('Last-Modified: ' . gmdate('D, d M Y H:i:s', $mod_time) . ' GMT');
		
		$etag = 'cowl-' . dechex(crc32($this->path . $mod_time));
		
		header('ETag: "' . $etag . '"');
		
		if ( isset($_SERVER['HTTP_IF_NONE_MATCH'], $_SERVER['HTTP_CACHE_CONTROL'])
			&& ! strstr($_SERVER['HTTP_CACHE_CONTROL'], 'no-cache')
			&& strstr($_SERVER['HTTP_IF_NONE_MATCH'], $etag) )
		{
			header('HTTP/1.1 304 Not Modified');
		}
		else
		{
			readfile($this->path);
		}
	}
	
	/*
		Method:
			setPath
		
		Set the path for the request. This will be modified to the actual path on disk, if
		it exists. Otherwise the <StaticServer::$is_file>-property will be set to false.
		
		Parameters:
			(string) $path - The path of the request
	*/
	
	public function setPath($path)
	{
		if ( $this->is_locked ) return;
		$this->path = $path;
		$this->parsePath();
	}
	
	/*
		Method:
			lockPath
		
		Lock path so subsequent calls to <StaticServer::setPath> are ignored.
	*/
	
	public function lockPath()
	{
		$this->is_locked = true;
	}
	
	/*
		Method:
			unlockPath
		
		Unlock the path, if previously locked by <StaticServer::lockPath>
	*/
	
	public function unlockPath()
	{
		$this->is_locked = false;
	}
	
	/*
		Method:
			forceSetPath
		
		Force set path without checking if it exists.
		
		Parameters:
			(string) $path - The path of the request
	*/
	
	public function forceSetPath($path)
	{
		$this->path = $path;
	}
	
	/*
		Method:
			setIsFile
		
		Force set if StaticServer::$is_file.
		
		Parameters:
			$is_file - The bool value to set it to
	*/
	
	public function setIsFile($is_file)
	{
		$this->is_file = $is_file;
	}
	
	/*
		Method:
			setDir
		
		Set the dir in which all static files are contained.
	*/
	
	public static function setDir($dir)
	{
		self::$files_dir = $dir;
	}
	
	// Method: <StaticServer::getPath>
	// Return <StaticServer::$path>
	public function getPath() { return $this->path; }
	
	// Method: <StaticServer::getType>
	// Return <StaticServer::$type>
	public function getType() { return $this->type; }
}
