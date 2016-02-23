<?php
	
	require_once solution\framework."exception.php";

	require_once solution\lib."imageresize/imageresize.php";

	use \Eventviva\ImageResize;

	class File_Upload_Exception extends Handler_Exception { };
	
	class File_Upload_Input
	{
		private $input = array();
		private $directory;
		private $files_data = array();

		public function __construct(array $input, $dir)
		{
			$this->input 	 = $input;
			$this->directory = $dir;
		}
		
		public function data()
		{
			return $this->files_data;
		}

		public function each($fn)
		{
			$files = array();

			foreach($this->input as $key => $file)
			{
				try
				{	
					$file_handler = new File_Upload_handler($file, $this->directory);

					$files[$key] = $fn($file_handler);

					$this->files_data[$key] = $file_handler->data();
				}
				catch(File_Upload_Exception $e)
				{

				};
			};

			return $files;
		}

	}

	class File_Upload_handler
	{
		private $file;
		private $directory;
		private $success;
		private $token;

		public function __construct(array $file, $directory)
		{
			$this->file 	 = $file;
			$this->directory = $directory;
		}

		public function delete()
		{
			if(file_exists("{$this->directory}/{$this->token}"))

				unlink("{$this->directory}/{$this->token}");
		}

		public function data()
		{
			return $this->file;
		}

		public function allocate()
		{
			$id = $this->token = uniqid();

			if(!is_dir($this->directory)) 

				throw new File_Upload_Exception("Diretorio de destino inexistente: '{$this->directory}'", 1);

			$image = new ImageResize($this->tmp_name());

			list($width, $height, $type, $attr) = getimagesize($this->tmp_name());

			$max_pixel_size = 2048;			
			
			$size = ($width>$height)?$width:$height;

			if($size>$max_pixel_size)

				$image->scale(($max_pixel_size / $size)*100);
			
			if($image->save("{$this->directory}/{$this->token}"))
			{
				$this->file["id"]		= $this->token;
				$this->file["message"]	= "Arquivo enviado com sucesso.";
				$this->file["success"]	= true;
			
				$this->success = true;

				return $this->token;

			} else {

				$this->file["message"] = "Arquivo nao enviado.";
				$this->file["success"] = false;

				$this->success = false;

				throw new File_Upload_Exception("Nao foi possivel mover o arquivo", 1);
			};

		}

		public function name()
		{
			return isset($this->file["name"])?$this->file["name"]:"";
		}

		public function type()
		{
			return isset($this->file["type"])?$this->file["type"]:"";
		}

		public function tmp_name()
		{
			return isset($this->file["tmp_name"])?$this->file["tmp_name"]:"";
		}

		public function error()
		{
			return isset($this->file["error"])?$this->file["error"]:"";
		}

		public function size()
		{
			return isset($this->file["size"])?$this->file["size"]:"";
		}

		public function id()
		{
			return isset($this->file["id"])?$this->file["id"]:"";
		}

		public function message()
		{
			return isset($this->file["message"])?$this->file["message"]:"";
		}

		public function success()
		{
			return $this->success;
		}

	}

	class File_Upload
	{	

		private static $allowed_formats = array();

		private static $max_size = 2048;

		private static $directory;

		private static $return_data=array();

		public static function data()
		{
			return self::$return_data;
		}

		public static function just_images()
		{
			self::allowed_formats(array("jpeg","jpg","png","bmp"));
		}

		public static function max_mb($size)
		{
			self::$max_size = 1024 * 1024 * $size;
		}

		public static function directory($dir)
		{
			self::$directory = $dir;
		}

		public static function max_kb($size)
		{
			self::$max_size = $size;
		}

		public static function allowed_formats(array $arr)
		{	
			self::$allowed_formats = $arr;
		}

		public static function each($fn)
		{	
			$inputs = array();

			foreach($_FILES as $input_name => &$input_data)
			{
				try
				{	
					$input_data = self::adjust_files($input_data);

					$input = new File_upload_input($input_data, self::$directory);		
					
					$inputs[$input_name] = $fn($input);
					
					self::$return_data = $input->data();
				}
				catch(File_Upload_Exception $e)
				{
					
				};
			};

			return $inputs;
		}

		private static function adjust_files(&$file_post) 
		{
		    $file_ary = array();
		    $file_count = count($file_post['name']);
		    $file_keys = array_keys($file_post);

		    for ($i=0; $i<$file_count; $i++) 
		    {
		        foreach ($file_keys as $key) 
		        {
		            $file_ary[$i][$key] = $file_post[$key][$i];
		        };
		    };

		    return $file_ary;
		}

		/*public static function upload()
		{
			
			$max_size  =  self::$max_size;
			
			$extensoes = self::$allowed_formats;
			
			//$_FILES['arquivo']['size']

			if(test_handler::get())
			{
				return array(
					"images" => array(
						array(
							"name"=>"logo (1) quadrada.png",
							"type"=>"image/png",
							"tmp_name"=>"/tmp/phpkOv4ut",
							"error"=>0,
							"size"=>57775,
							"id"=>"042412a9d8d6a355fa9286cf65dfa834",
							"message"=>"Imagem enviada com sucesso.",
							"success"=>true
							),
						array(
							"name"=>"LogoTavarnaroQuemSomos.jpg",
							"type"=>"image/jpeg",
							"tmp_name"=>"/tmp/phpTW1dOC",
							"error"=>0,
							"size"=>9752,
							"ID"=>"576f340fd50acc54dffffe70bb70a749",
							"message"=>"Imagem enviada com sucesso.",
							"success"=>true
							)
						)
					);
			};

			$query = $_SERVER['QUERY_STRING'];

			$return_data = array();

			foreach($_FILES as $input_name => $input_data)
			{
				try
				{						
					$return_data[$input_name] = self::upload_by_input_data($input_data);
				}
				catch(Exception $e)
				{
					$h = new handler_exception();
					$h->incorpore($e);
					$return_data[$input_name] = $h->data();
				};
			};
			
			OB::save();

			return $return_data;
		}

		private static function upload_by_input_data($input)
		{
			try{

				$files = self::adjust_files($input);

				$return_data = array();

				foreach($files as $key => $file)
				{
					try{

						$id = md5(uniqid(rand(),true));

						$upload_path = solution."files/images/{$id}.jpg";
						
						$success = move_uploaded_file($file['tmp_name'], $upload_path);

						if($success) 
						{
							$file["id"] 	 = $id;
							$file["message"] = "Imagem enviada com sucesso.";
							$file["success"] = true;

							$return_data[$key] = $file;
						} else 
						{
							$file["message"] = "Imagem nao enviada.";
							$file["success"] = false;

							$return_data[$key] = $file;
						};
					}
					catch(Exception $e)
					{
						$file["success"] = false;
						$file["message"] = "Uma exception foi executada";
						$file["exception"] = $e->getMessage()." - ".$e->getFile()." - ".$e->getLine();
						$return_data[$key] = $file;
					};
				};
				
				return $return_data;

			}catch(Exception $e)
			{
				return array("success"=>"false", "message"=>"Ocorreu um erro");
			};
		}
		*/

	}