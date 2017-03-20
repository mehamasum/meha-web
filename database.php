<?php
class Database {

    private $link;

    public function __construct() {
        $host = '';
        $db = '';
        $user = '';
        $pass = '';
        foreach ($_SERVER as $key => $value) {
            if (strpos($key, "MYSQLCONNSTR_localdb") !== 0) {
                continue;
            }

            $host = preg_replace("/^.*Data Source=(.+?);.*$/", "\\1", $value);
            $db = preg_replace("/^.*Database=(.+?);.*$/", "\\1", $value);
            $user = preg_replace("/^.*User Id=(.+?);.*$/", "\\1", $value);
            $pass = preg_replace("/^.*Password=(.+?)$/", "\\1", $value);
        }

        try{
            $this->link = new PDO("mysql:host=".$host.";dbname=".$db, $user, $pass);
            $this->link->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        }
        catch (PDOException $e){
            echo "Error: Unable to connect to MySQL: ". $e->getMessage();
            die;
        }

        $this->InitializeImageTable();
    }

    public function __destruct() {
        $this->link = null;
    }

    public function UploadImage($imageName, $imageFP) {
        $sql = $this->link->prepare("INSERT INTO images (name, image) VALUES (:name, :image);");
        $sql->bindParam(":name", $imageName);
        $sql->bindParam(":image", $imageFP, PDO::PARAM_LOB);

        $sql->execute();

        return $this->link->lastInsertId();
    }

    public function GetAllImages() {
        $sql = $this->link->prepare("SELECT * FROM images;");
        $sql->execute();

        $results = $sql->fetchAll(PDO::FETCH_OBJ);

        return $results;
    }

    public function FindImage($id) {
        $sql = $this->link->prepare("SELECT * FROM images WHERE id = :id;");
        $sql->bindParam(":id", $id, PDO::PARAM_INT);
        $sql->execute();

        $result = $sql->fetch(PDO::FETCH_OBJ);
        return $result;
    }

    private function InitializeImageTable() {
        // Check to see if the table needs to be created
        $results = $this->link->query("SHOW TABLES LIKE 'images';");
        if ($results == TRUE && $results->rowCount() > 0) {
            return;
        }

        // create table
        $sql = "CREATE TABLE images (
                id INT(10) NOT NULL AUTO_INCREMENT PRIMARY KEY, 
                name VARCHAR(255) NOT NULL DEFAULT '',
                image LONGBLOB NOT NULL
                );";

        if ($this->link->query($sql) != TRUE) {
            die("Error creating image table: " . $this->link->error);
        }
    }
}
?>