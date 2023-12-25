<?php 

namespace App\controllers;

use PDO;
use RedBeanPHP\Facade as R;

class User
{
    private $imgMaxSize = 100000000;

    public function getUser($id)
    {
        try {
            $connect = (new SQLiteConnection())->connect();
        }
        catch (PDOException $e) {
            $message = date("H:i:s") . ' - getUser -' . $e->getMessage() . "\n";
            file_put_contents(date("Y-m-d") . "errors.txt", $message, FILE_APPEND);
        }
        $sql = "SELECT * FROM users WHERE id = :id";
        $stmt = $connect->prepare($sql);
        $stmt->bindValue(":id", $id);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        if($result){
            return $result;
        }
        return false;
    }
    public function signup()
    {
        $nickname = $_POST['nickname'];
        $email = $_POST['email'];
        $password = $_POST['password'];
        $password_confirm = $_POST['password_confirm'];
        $path = "assets/img/default/avatar-default.jpg";
        if(!filter_var($email, FILTER_VALIDATE_EMAIL)){
            $_SESSION['errors'] = "Неправильная почта.";
            return header("Location: /signup");
        }
        if($password != $password_confirm) {
            $_SESSION['errors'] = "Пароли не совпадают. Попробуйте еще раз.";
            return header("Location: /signup");
        }
        if(!$this->checkEmail($email)){
            $_SESSION['errors'] = "Пользователь с данной почтой уже зарегистрирован в чате. Попробуйте использовать другую почту.";
            return header("Location: /signup");
        }
        if(!$this->checkNickname($nickname)){
            $_SESSION['errors'] = "Пользователь с данным ником уже присутствует в чате. Попробуйте выбрать другой nickname.";
            return header("Location: /signup");
        }
        if(isset($_FILES['image']) && $_FILES['image']['size'] > 0){
            $img_name = $_FILES['image']['name'];
            $img_type = $_FILES['image']['type'];
            $tmp_name = $_FILES['image']['tmp_name'];
            
            $img_explode = explode('.',$img_name);
            $img_ext = end($img_explode);

            if($_FILES['image']['size'] > $this->imgMaxSize){
                $_SESSION['errors'] = "Картинка не должна весить более 10 Mb.";
                return header("Location: /signup");
            }
            $extensions = ["jpeg", "png", "jpg"];

            if(in_array($img_ext, $extensions) === true){
                $types = ["image/jpeg", "image/jpg", "image/png"];
                if(in_array($img_type, $types) === true){
                    $path = 'assets/img/' . time() . $_FILES['image']['name'];
                    if (!move_uploaded_file($tmp_name, $path)) {
                        $_SESSION['errors'] = "Ошибка при загрузке картинки. Попробуйте ещё раз.";
                        return header("Location: /signup");
                    }
                } else {
                    $_SESSION['errors'] = "Неправильный формат файла. Должен быть один из jpeg, png, jpg.";
                    return header("Location: /signup");
    
                }
            } else {
                $_SESSION['errors'] = "Неправильный формат файла. Должен быть один из jpeg, png, jpg.";
                return header("Location: /signup");
            }
        }

        try {
            $connect = (new SQLiteConnection())->connect();
        }
        catch (PDOException $e) {
            $message = date("H:i:s") . ' - signup -' . $e->getMessage() . "\n";
            file_put_contents(date("Y-m-d") . "errors.txt", $message, FILE_APPEND);
        }

        $password = md5($password);
        $sql = "INSERT INTO users (email, password, nickname, photo_file) VALUES (:email, :password, :nickname, :photo_file)";
        $stmt = $connect->prepare($sql);
        // привязываем параметры к значениям
        $stmt->bindValue(":email", $email);
        $stmt->bindValue(":password", $password);
        $stmt->bindValue(":nickname", $nickname);
        $stmt->bindValue(":photo_file", $path);
        // выполняем prepared statement
        $affectedRowsNumber = $stmt->execute();
        unset($_SESSION['errors']);
        if($affectedRowsNumber) {
            mail($email, 'Добро пожаловать в чат', 'Подтвердите свою регистрацию');
            return header("location: /login");
        }
    }

    public function checkEmail($email)
    {
        try {
            $connect = (new SQLiteConnection())->connect();
        }
        catch (PDOException $e) {
            $message = date("H:i:s") . ' - checkEmail -' . $e->getMessage() . "\n";
            file_put_contents(date("Y-m-d") . "errors.txt", $message, FILE_APPEND);
        }
        $sql = "SELECT * FROM users WHERE email = :email";
        $stmt = $connect->prepare($sql);
        $stmt->execute([":email" => $email]);

        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if($result){
            return false;
        }
        return true;
    }
    public function checkNickname($nickanme)
    {
        try {
            $connect = (new SQLiteConnection())->connect();
        }
        catch (PDOException $e) {
            $message = date("H:i:s") . ' - checNickname -' . $e->getMessage() . "\n";
            file_put_contents(date("Y-m-d") . "errors.txt", $message, FILE_APPEND);
        }
        $sql = "SELECT * FROM users WHERE nickname = :nickname";
        $stmt = $connect->prepare($sql);
        $stmt->execute([":nickname" => $nickanme]);

        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if($result){
            return false;
        }
        return true;
    }

    public function login()
    {
        $email = $_POST['email'];
        $password = $_POST['password'];
        try {
            $connect = (new SQLiteConnection())->connect();
        }
        catch (PDOException $e) {
            $message = date("H:i:s") . ' - login -' . $e->getMessage() . "\n";
            file_put_contents(date("Y-m-d") . "errors.txt", $message, FILE_APPEND);
        }
        $sql = "SELECT * FROM users WHERE email = :email and password = :password";
        $stmt = $connect->prepare($sql);
        $stmt->execute([":email" => $email, ":password" => md5($password)]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        if($result) {
            $_SESSION['user']['nickname'] = $result['nickname']; 
            $_SESSION['user']['photo_file'] = $result['photo_file'] ?? "assets/img/default/avatar-default.jpg"; 
            $_SESSION['user']['id'] = $result['id']; 
            unset($_SESSION['errors']);
            $sql = "UPDATE users SET is_active = 1  WHERE id = :id";
            $stmt = $connect->prepare($sql);
            $stmt->execute([":id" => $result['id']]);

            return header("Location: /chat");
        }else {
            $_SESSION['errors'] = "Учётная запись не найдена.";
            return header("Location: /login");
        }
    }
    public function searchUsers()
    {
        if((isset($_POST['searchTerm']) && $_POST['searchTerm'] != "") || isset($_POST['user_id'])) {
            try {
                $connect = (new SQLiteConnection())->connect();
            }
            catch (PDOException $e) {
                $message = date("H:i:s") . ' - searchUsers -' . $e->getMessage() . "\n";
                file_put_contents(date("Y-m-d") . "errors.txt", $message, FILE_APPEND);
            }
            $output = "";
            if((isset($_POST['searchTerm']) && $_POST['searchTerm'] != "")) {
                $sql = "SELECT * FROM users WHERE id != :val1 AND (nickname LIKE :val2 OR email LIKE :val3) ";
                $stmt = $connect->prepare($sql);
                $stmt->bindValue(':val1', $_SESSION['user']['id']);
                $stmt->bindValue(':val2', '%' . $_POST['searchTerm'] . '%', PDO::PARAM_STR);
                $stmt->bindValue(':val3', '%' . $_POST['searchTerm'] . '%', PDO::PARAM_STR);
            }
            // if(isset($_POST['user_id'])) {
            //     $sql = "SELECT chat_users FROM chats WHERE chat_users LIKE :user_id AND type = 'single'";
            //     $stmt = $connect->prepare($sql);
            //     $stmt->execute([":user_id" => '%' . $_POST['user_id'] . '%']);
            //     $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
            //     $contacts = [];
            //     foreach ($result as $item) {
            //         $contacts = array_merge($contacts, explode(',', $item['chat_users']));
            //     }
            //     $contacts = array_unique($contacts);
            //     unset($contacts[array_search($_POST['user_id'], $contacts)]);
            //     $contacts = implode(',', $contacts);

            //     $sql = "SELECT * FROM users WHERE id IN ($contacts)";
            //     $stmt = $connect->prepare($sql);
            // }
            $stmt->execute();
            $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
            // echo '<pre>';var_dump($result);echo '</pre>'; die();

            if($result ){
                foreach ($result as $row) {
                    if(mb_stripos($row['email'], $_POST['searchTerm']) !== false && mb_stripos($row['nickname'], $_POST['searchTerm']) === false && !$row['show_email']) {
                        continue;
                    }
                    $photo = $row['photo_file'];
                    $output .= '<div class="open_chat">
                                    <div class="content" >
                                        <img src="'. $photo .'" alt="">
                                        <div class="details">
                                            <span id="' . $_SESSION['user']['id'] . '/' . $row['id'] . '/check_chat" title="Открыть чат">' . $row['nickname'] . '</span>
                                        </div>
                                    </div>
                                </div>';
                }
            }else{
                $output .= 'No user found related to your search term';
            }
            echo $output;
        }
    }
    public function logout($id)
    {
        session_destroy();
        return header("Location: /login");
    }

    public function changeInfo()
    {
        $nickname = $_POST['nickname'] ?? null;
        $hideEmail = $_POST['hideEmail'] ?? null;
        $userId = $_POST['user_id'] ?? null;
        unset($_SESSION['errors']);

        if(isset($_FILES['avatar']) && $_FILES['avatar']['size'] > 0){
            $img_name = $_FILES['avatar']['name'];
            $img_type = $_FILES['avatar']['type'];
            $tmp_name = $_FILES['avatar']['tmp_name'];
            
            $img_explode = explode('.',$img_name);
            $img_ext = end($img_explode);

            if($_FILES['avatar']['size'] > $this->imgMaxSize){
                $_SESSION['errors'] = "Картинка не должна весить более 10 Mb.";
                return header("Location: /signup");
            }
            $extensions = ["jpeg", "png", "jpg"];

            if(in_array($img_ext, $extensions) === true){
                $types = ["image/jpeg", "image/jpg", "image/png"];
                if(in_array($img_type, $types) === true){
                    $path = 'assets/img/' . time() . $_FILES['avatar']['name'];

                    if (!move_uploaded_file($tmp_name, $path)) {
                        $_SESSION['errors'] = "Ошибка при загрузке картинки. Попробуйте ещё раз.";
                        return header("Location: /signup");
                    }
                } else {
                    $_SESSION['errors'] = "Неправильный формат файла. Должен быть один из jpeg, png, jpg.";
                    return header("Location: /signup");
    
                }
            } else {
                $_SESSION['errors'] = "Неправильный формат файла. Должен быть один из jpeg, png, jpg.";
                return header("Location: /signup");
            }
        }

        try {
            $connect = (new SQLiteConnection())->connect();
        }
        catch (PDOException $e) {
            $message = date("H:i:s") . ' - changeInfo -' . $e->getMessage() . "\n";
            file_put_contents(date("Y-m-d") . "errors.txt", $message, FILE_APPEND);
        }
        if($nickname && $nickname != "") {
            $sql = "SELECT * FROM users WHERE nickname = :nickname";
            $stmt = $connect->prepare($sql);
            $stmt->execute([":nickname" => $nickname]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);

            if(!$result) {
                $sql = "UPDATE users SET nickname = :nickname  WHERE id = :id";
                $stmt = $connect->prepare($sql);
                $stmt->execute([":nickname" => $nickname, ":id" => $userId]);
                $_SESSION['user']['nickname'] = $nickname; 
            } else {
                $_SESSION['errors'] = "Данный nickname уже занят, попробуйте другой.";
            }
        }
        if(isset($hideEmail)) {
            $sql = "UPDATE users SET show_email = 0  WHERE id = :id";
            $stmt = $connect->prepare($sql);
            $stmt->execute([":id" => $userId]);
        }
        if(isset($path)) {
            $sql = "UPDATE users SET photo_file = :photo_file  WHERE id = :id";
            $stmt = $connect->prepare($sql);
            $stmt->execute([":photo_file" => $path, ":id" => $userId]);
            $_SESSION['user']['photo_file'] = $path ?? "assets/img/default/avatar-default.jpg"; 
        }
        return header("Location: /chat");
    }

    public function getContacts()
    {
        // var_dump($_POST); die();
        try {
            $connect = (new SQLiteConnection())->connect();
        }
        catch (PDOException $e) {
            $message = date("H:i:s") . ' - getContacts -' . $e->getMessage() . "\n";
            file_put_contents(date("Y-m-d") . "errors.txt", $message, FILE_APPEND);
        }
        $sql = "SELECT chat_users FROM chats WHERE chat_users LIKE :user_id";
        $stmt = $connect->prepare($sql);
        $stmt->execute([":user_id" => '%' . $_POST['user_id'] . '%']);
        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $contacts = [];
        foreach ($result as $item) {
            $contacts = array_merge($contacts, explode(',', $item['chat_users']));
        }
        $contacts = array_unique($contacts);
        unset($contacts[array_search($_POST['user_id'], $contacts)]);
        $contacts = implode(',', $contacts);

        $sql = "SELECT * FROM users WHERE id IN ($contacts)";
        $stmt = $connect->prepare($sql);
        $stmt->execute();
        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // echo '<pre>';var_dump($result);echo '</pre>'; die();
        echo json_encode(["status" => "ok", "data" => $result]);
    }
}