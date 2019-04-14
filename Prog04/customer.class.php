<?php
if (!$_SESSION) {
    header("Location: login.php");
}

class Customer {
    public $id;
    public $name;
    public $email;
    public $mobile;
    public $password; // text from HTML form
    public $password_hashed; // hashed password
    private $sessionid = null;
    private $noerrors = true;
    private $nameError = null;
    private $emailError = null;
    private $mobileError = null;
    private $passwordError = null;
    private $confirmCodeError = null;
    private $confirmPasswordError = 'Changing password is optional';
    private $title = "Customer";
    private $tableName = "customers";
    
    function create_record() { // display "create" form
        $this->generate_html_top (1);
        $this->generate_form_group("Name", $this->nameError, $this->name, "autofocus");
        $this->generate_form_group("Email", $this->emailError, $this->email);
        $this->generate_form_group("Mobile", $this->mobileError, $this->mobile);
		$this->generate_form_group("Password", $this->passwordError, $this->password, "", "password");
		$this->display_file_upload();
        $this->generate_html_bottom (1);
    } // end function create_record()
    
    function read_record($id) { // display "read" form
        $this->select_db_record($id);
        $this->generate_html_top(2);
		$this->display_photo();
        $this->generate_form_group("Name", $this->nameError, $this->name, "disabled");
        $this->generate_form_group("Email", $this->emailError, $this->email, "disabled");
        $this->generate_form_group("Mobile", $this->mobileError, $this->mobile, "disabled");
        $this->generate_html_bottom(2);
    } // end function read_record()
    
    function update_record($id) { // display "update" form
        if($this->noerrors) $this->select_db_record($id);
        $this->generate_html_top(3, $id);
        $this->generate_form_group("Name", $this->nameError, $this->name, "autofocus onfocus='this.select()'");
        $this->generate_form_group("Email", $this->emailError, $this->email);
        $this->generate_form_group("Mobile", $this->mobileError, $this->mobile);
		$this->generate_form_group("Password", $this->passwordError, $this->password, "", "password");
		$this->display_file_upload();
        $this->generate_html_bottom(3);
    } // end function update_record()
    
    function delete_record($id) { // display "read" form
        $this->select_db_record($id);
        $this->generate_html_top(4, $id);
        $this->generate_form_group("Name", $this->nameError, $this->name, "disabled");
        $this->generate_form_group("Email", $this->emailError, $this->email, "disabled");
        $this->generate_form_group("Mobile", $this->mobileError, $this->mobile, "disabled");
        $this->generate_html_bottom(4);
    } // end function delete_record()
    
    function insert_db_record () {
		if (isset($_SESSION['name'])) $this->name = $_SESSION['name'];
		if (isset($_SESSION['email'])) $this->email = $_SESSION['email'];
		if (isset($_SESSION['mobile'])) $this->mobile = $_SESSION['mobile'];
		if (isset($_POST["password"]))   $this->password = htmlspecialchars($_POST["password"]);
		
		$fileDescription = $_POST['Description']; 
		$fileName       = $_FILES['Filename']['name'];
		$tempFileName   = $_FILES['Filename']['tmp_name'];
		$fileSize       = $_FILES['Filename']['size'];
		$fileType       = $_FILES['Filename']['type'];
		
		if($fileSize > 2000000) { echo "Error: file exceeds 2MB."; exit(); }
		
		$content = file_get_contents($tempFileName);
		if ($fileName != "") {
			$filePath = substr($this->get_current_url(), 0, 44);
			$filePath = "https://cis255gemalisk.000webhostapp.com/cs355Prog04/uploads/" . $fileName;
		}
			if ($this->fieldsAllValid ()) { // validate user input
				$this->save_file_to_directory();
				$pdo = Database::connect();
				$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
				$this->password_hashed = MD5($this->password);
				$sql = "INSERT INTO $this->tableName (name,email,mobile,password_hash,filename,filesize,filetype,filecontent,filepath,description) values(?, ?, ?, ?, ?, ?, ?, ? ,? ,?)";
				$q = $pdo->prepare($sql);
				$q->execute(array($this->name,$this->email,$this->mobile,$this->password_hashed,$fileName,$fileSize,$fileType,$content,$filePath,$fileDescription));
				Database::disconnect();
				if (isset($_SESSION["user_id"])){
					header("Location: $this->tableName.php?fun=display_list"); // return to "list"
				}
				else {
					header("Location: login.php"); //go to login
				}
			}
			else {
				$this->create_record(); 
			}
    } // end function insert_db_record
    
    private function select_db_record($id) {
        $pdo = Database::connect();
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $sql = "SELECT * FROM $this->tableName where id = ?";
        $q = $pdo->prepare($sql);
        $q->execute(array($id));
        $data = $q->fetch(PDO::FETCH_ASSOC);
        Database::disconnect();
        $this->name = $data['name'];
        $this->email = $data['email'];
        $this->mobile = $data['mobile'];
    } // function select_db_record()
    
    function update_db_record ($id) {
		$this->id = $id;
		if(isset($_POST["name"]))       $this->name = htmlspecialchars($_POST["name"]);
		if(isset($_POST["email"]))  	$this->email = htmlspecialchars($_POST["email"]);
		if(isset($_POST["mobile"]))     $this->mobile = htmlspecialchars($_POST["mobile"]);
			
		$fileDescription = $_POST['Description']; 
		$fileName       = $_FILES['Filename']['name'];
		$tempFileName   = $_FILES['Filename']['tmp_name'];
		$fileSize       = $_FILES['Filename']['size'];
		$fileType       = $_FILES['Filename']['type'];
			
		if($fileSize > 2000000) { echo "Error: file exceeds 2MB."; exit(); }
		
		$content = file_get_contents($tempFileName);
		if ($fileName != "") {
			$filePath = substr($this->get_current_url(), 0, 44);
			$filePath = "https://cis255gemalisk.000webhostapp.com/cs355Prog04/uploads/" . $fileName;
		}
			if ($this->fieldsAllValid ()) {
				$this->noerrors = true;
				if ($this->check_password()) {
					$pdo = Database::connect();
					$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
					$sql = "UPDATE $this->tableName  set name = ?, email = ?, mobile = ? WHERE id = ?";
					$q = $pdo->prepare($sql);
					$q->execute(array($this->name,$this->email,$this->mobile,$this->id));
					
					if($fileName != "") {
						$this->save_file_to_directory();
						$sql = "UPDATE $this->tableName  set filename = ?,filesize = ?,filetype = ?,filecontent = ?,filepath = ?,description = ? WHERE id = ?";
						$q = $pdo->prepare($sql);
						$q->execute(array($fileName,$fileSize,$fileType,$content,$filePath,$fileDescription,$this->id));
					}
					
				Database::disconnect();
				header("Location: $this->tableName.php?fun=display_list");
				}
			}
			else {
				$this->noerrors = false;
				$this->update_record($id);  // go back to "update" form
			}
	}//end function update db record
    
    function delete_db_record($id) {
        $pdo = Database::connect();
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $sql = "DELETE FROM $this->tableName WHERE id = ?";
        $q = $pdo->prepare($sql);
        $q->execute(array($id));
        Database::disconnect();
        header("Location: $this->tableName.php?fun=display_list");
    } // end function delete_db_record()
	
	function display_photo() {
		if (isset($_GET['id'])){
			$id = $_GET['id'];
			echo "<div> ";
			
			$pdo = Database::connect();
			$sql = "SELECT * FROM $this->tableName WHERE id = $id";
			$data = $pdo->query($sql);
			$row = $data->fetch(PDO::FETCH_ASSOC);
			
			if ($row["filesize"] > 0) {
				echo "<img height='100' width='100' src = 'data:image/jpeg;base64," . base64_encode($row["filecontent"]). "' />";
			}
			else {
				echo "<p>No photo on file</p>";
			}
			
			echo "</div> <br>";
			Database::disconnect();
		}
		else {
			echo "ID is not set";
		}
	}
	
	function save_file_to_directory(){
		$fileName       = $_FILES['Filename']['name'];
		$tempFileName   = $_FILES['Filename']['tmp_name'];
		$fileSize       = $_FILES['Filename']['size'];
		$fileType       = $_FILES['Filename']['type'];
		$fileLocation = 'uploads/';
		$fileFullPath = $fileLocation . $fileName; 
		if (!file_exists($fileLocation)) {
			mkdir ($fileLocation); // create subdirectory, if necessary
		}
		
		// if file does not already exist, upload it
		if (!file_exists($fileFullPath)) {
			$result = move_uploaded_file($tempFileName, $fileFullPath);
			if ($result) {
				echo "Upload successful. " . $fileName;
			} 
			else {
				echo "Upload denied for file. " . $fileName 
					. "</i></b>. Verify file size < 2MB. ";
			}
		}
		else { // file already exists error message
			echo "File <b><i>" . $fileName 
				. "</i></b> already exists. Please rename file.";
		}
	}
	
	function get_current_url($strip = true) {
		$filter = "";
		$scheme; 
		$host;
		if (!$filter) {
			$filter = function($input) use($strip) {
				$input = trim($input);
				if ($input == '/') {
					return $input;
				}
				$input = str_ireplace(["\0", '%00', "\x0a", '%0a', "\x1a", '%1a'], '', rawurldecode($input));
				if ($strip) {
					$input = strip_tags($input);
				}
				$input = htmlspecialchars($input, ENT_QUOTES, 'utf-8');
				return $input;
			};
			$host = $_SERVER['SERVER_NAME'];
			$scheme = isset($_SERVER['REQUEST_SCHEME']) ? $_SERVER['REQUEST_SCHEME'] : ('http' . (($_SERVER['SERVER_PORT'] == '443') ? 's' : ''));
		}
		return sprintf('%s://%s%s', $scheme, $host, $filter($_SERVER['REQUEST_URI']));
	}
	
	function display_file_upload() {
		echo "
				<p>File</p>
				<input type='file' name='Filename'> 
				<p>Description</p>
				<textarea rows='10' cols='35' name='Description'></textarea>
				<p>*Max of 255 characters.</p>
				<br/>";
	}
   
	private function check_password() {
		$valid = true;
		$pdo = Database::connect();
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
		$this->password_hashed = MD5($this->password);
		$sql = "SELECT * FROM $this->tableName WHERE id = ? AND password_hash = ? LIMIT 1";
		$q = $pdo->prepare($sql);
		$q->execute(array($this->id,$this->password_hashed));
		$data = $q->fetch(PDO::FETCH_ASSOC);
		
		if (!($data)) {
			Database::disconnect();
			$valid = false;
			$this->passwordError = 'Incorrect password, unable to change user information';
			$this->update_record($this->id);
		}
		
		Database::disconnect();
		return;
	}
	
	
    private function generate_html_top ($fun, $id=null) {
        switch ($fun) {
            case 1: // create
                $funWord = "Create"; $funNext = "insert_db_record"; 
                break;
            case 2: // read
                $funWord = "Read"; $funNext = "none"; 
                break;
            case 3: // update
                $funWord = "Update"; $funNext = "update_db_record&id=" . $id; 
                break;
            case 4: // delete
                $funWord = "Delete"; $funNext = "delete_db_record&id=" . $id; 
                break;
            default: 
                echo "Error: Invalid function: generate_html_top()"; 
                exit();
                break;
        }
        echo "<!DOCTYPE html>
        <html>
            <head>
                <title>$funWord a $this->title</title>
                    ";
        echo "
                <meta charset='UTF-8'>
                <link href='https://stackpath.bootstrapcdn.com/bootstrap/4.1.2/css/bootstrap.min.css' rel='stylesheet'>
                <script src='https://stackpath.bootstrapcdn.com/bootstrap/4.1.2/js/bootstrap.min.js'></script>
                <style>label {width: 5em;}</style>
                    "; 
        echo "
            </head>";
        echo "
            <body>
                <div class='container'>
                    <div class='span10 offset1'>
                        <p class='row'>
                            <h3>$funWord a $this->title</h3>
                        </p>
                        <form class='form-horizontal' action='$this->tableName.php?fun=$funNext' method='post' enctype='multipart/form-data'>                        
                    ";
    } // end function generate_html_top()
    
    private function generate_html_bottom ($fun) {
        switch ($fun) {
            case 1: // create
                $funButton = "<button type='submit' class='btn btn-success'>Create</button>"; 
                break;
            case 2: // read
                $funButton = "";
                break;
            case 3: // update
                $funButton = "<button type='submit' class='btn btn-warning'>Update</button>";
                break;
            case 4: // delete
                $funButton = "<button type='submit' class='btn btn-danger'>Delete</button>"; 
                break;
            default: 
                echo "Error: Invalid function: generate_html_bottom()"; 
                exit();
                break;
        }
        echo " 
                            <div class='form-actions'>
                                $funButton
                                <a class='btn btn-secondary' href='$this->tableName.php?fun=display_list'>Back</a>
                            </div>
                        </form>
                    </div>
                </div> <!-- /container -->
            </body>
        </html>
                    ";
    } // end function generate_html_bottom()
    
	 private function generate_form_group ($label, $labelError, $val, $modifier="", $fieldType="text") {
        echo "<div class='form-group";
        echo !empty($labelError) ? ' alert alert-danger ' : '';
        echo "'>";
        echo "<label class='control-label'>$label &nbsp;</label>";
        echo "<input "
            . "name='$label' "
            . "type='$fieldType' "
            . "$modifier "
            . "placeholder='$label' "
            . "value='";
        echo !empty($val) ? $val : '';
        echo "'>";
        if (!empty($labelError)) {
            echo "<span class='help-inline'>";
            echo "&nbsp;&nbsp;" . $labelError;
            echo "</span>";
        }
        echo "</div>";
    } // end function generate_form_group()
    
    private function fieldsAllValid () {
        $valid = true;
        if (empty($this->name)) {
            $this->nameError = 'Please enter Name';
            $valid = false;
        }
        if (empty($this->email)) {
            $this->emailError = 'Please enter Email Address';
            $valid = false;
        } 
        else if ( !filter_var($this->email,FILTER_VALIDATE_EMAIL) ) {
            $this->emailError = 'Please enter a valid email address: me@mydomain.com';
            $valid = false;
        }
        if (empty($this->mobile)) {
            $this->mobileError = 'Please enter Mobile phone number';
            $valid = false;
        }
		if (empty($this->password)){
			$this->passwordError = 'Please enter a password';
			$valid = false;
		}
		return $valid;
    } // end function fieldsAllValid() 
	
	function list_pics() {
		 echo "<!DOCTYPE html>
        <html>
            <head>
                <title>$this->title" . "s" . "</title>
                    ";
        echo "
                <meta charset='UTF-8'>
                <link href='https://stackpath.bootstrapcdn.com/bootstrap/4.1.2/css/bootstrap.min.css' rel='stylesheet'>
                <script src='https://stackpath.bootstrapcdn.com/bootstrap/4.1.2/js/bootstrap.min.js'></script>
                    ";  
        echo "
            </head>
            <body>
                <div class='container'>
                    <p class='row'>
                        <h3>Pictures</h3>
                    </p>
					<p>
						<a href='$this->tableName.php?fun=display_list' class='btn btn-secondary'>Back</a>
					</p>
					
                    <div class='row'>
                        <table class='table table-striped table-bordered'>
                            <thead>
                                <tr>
                                    <th>FileName</th>
                                    <th>File Path</th>
                                    <th>Description</th>
                                    <th>Photo</th>
                                </tr>
                            </thead>
                            <tbody>
                    ";
        $pdo = Database::connect();
        $sql = "SELECT * FROM $this->tableName ORDER BY id DESC";
        foreach ($pdo->query($sql) as $row) {
            echo "<tr>";
            echo "<td>". $row["filename"] . "</td>";
            echo "<td><a href='" . $row["filepath"] . "'>" . $row["filepath"] . "</a></td>";
            echo "<td>". $row["description"] . "</td>";
            echo "<td>" . $row["name"] . "<br>";
			if ($row["filesize"] > 0) {
				echo "<img height='100' width='100' src = 'data:image/jpeg;base64," . base64_encode($row["filecontent"]). "' />";
			}
			else {
				echo "<p>No photo on file</p>";
			}
            echo "</td>";
            echo "</tr>";
        }
        Database::disconnect();        
        echo "
                            </tbody>
                        </table>
                    </div>
                </div>
            </body>
        </html>
                    ";  
	}
	
    function list_records() {
        echo "<!DOCTYPE html>
        <html>
            <head>
                <title>$this->title" . "s" . "</title>
                    ";
        echo "
                <meta charset='UTF-8'>
                <link href='https://stackpath.bootstrapcdn.com/bootstrap/4.1.2/css/bootstrap.min.css' rel='stylesheet'>
                <script src='https://stackpath.bootstrapcdn.com/bootstrap/4.1.2/js/bootstrap.min.js'></script>
                    ";  
        echo "
            </head>
            <body>
		<a href='https://github.com/gemalisk/CIS355Prog4' target='_blank'>Github Link</a><br />
		<a href='https://github.com' target='_blank'>UML Diagram</a><br />
                <div class='container'>
                    <p class='row'>
                        <h3>$this->title" . "s" . "</h3>
                    </p>
					<p>
					<a href= 'uploads/' class='btn btn-info'>All Uploaded Images</a>
						<a href='$this->tableName.php?fun=list_pics' class='btn btn-info'>Profile Picture Information</a>
					</p>
                    <p>
                        <a href='logout.php' class='btn btn-warning'>Log Out</a>
                    </p>
                    <div class='row'>
                        <table class='table table-striped table-bordered'>
                            <thead>
                                <tr>
                                    <th>Profile Picture</th>
                                    <th>Name</th>
                                    <th>Email</th>
                                    <th>Mobile</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                    ";
        $pdo = Database::connect();
        $sql = "SELECT * FROM $this->tableName ORDER BY id DESC";
        foreach ($pdo->query($sql) as $row) {
            echo "<tr>";
            if ($row["filesize"] > 0) {
				echo "<td> <img height='50' width='75' src = 'data:image/jpeg;base64," . base64_encode($row["filecontent"]). "' /> </td>";
			}
			else {
				echo "<td>No photo on file</td>";
			}
            echo "<td>". $row["name"] . "</td>";
            echo "<td>". $row["email"] . "</td>";
            echo "<td>". $row["mobile"] . "</td>";
            echo "<td width=250>";
            echo "<a class='btn btn-info' href='$this->tableName.php?fun=display_read_form&id=".$row["id"]."'>Read</a>";
            echo "&nbsp;";
            echo "<a class='btn btn-warning' href='$this->tableName.php?fun=display_update_form&id=".$row["id"]."'>Update</a>";
            echo "&nbsp;";
            echo "<a class='btn btn-danger' href='$this->tableName.php?fun=display_delete_form&id=".$row["id"]."'>Delete</a>";
            echo "</td>";
            echo "</tr>";
        }
        Database::disconnect();        
        echo "
                            </tbody>
                        </table>
                    </div>
                </div>
            </body>
        </html>
                    ";  
    } // end function list_records()
    
} // end class Customer