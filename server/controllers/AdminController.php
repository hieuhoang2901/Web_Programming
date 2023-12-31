<?php

namespace Controllers;

use blog_model;
use dish_model;
use comment_model;
use user_model;
use reservation_model;
use Db;
use Middleware\AuthMiddleware as AuthMiddleware;
use Middleware\FormMiddleware as FormMiddleware;

class AdminController
{
  public function changePublicInfo() {
    if (!$this->checkAdminRole()) {
      echo json_encode(["message" => "Invalid action. You are not admin", 'status' => 403]);
      return;
    }
    $payload = ['phone', 'address', 'openTime', 'closeTime'];
    $formValid = new FormMiddleware();
    $check = $formValid->checkFullFields($payload);
    if ($check) {
      $phone = $_POST['phone'];
      $address = $_POST['address'];
      $openTime = $_POST['openTime'];
      $closeTime = $_POST['closeTime'];
      $openTimeList = explode(':', $openTime);
      $closeTimeList = explode(':', $closeTime);
      $openTimeMinute = $openTimeList[0] * 60 + $openTimeList[1];
      $closeTimeMinute = $closeTimeList[0] * 60 + $closeTimeList[1];
      if ($openTimeMinute > $closeTimeMinute) {
        echo json_encode(['message' => "Open and close time are not valid!", 'status' => 400]);
        return;
      }
      $db = Db::getInstance();
      $sql = "update public_infomation set phone = '$phone', address = '$address', open_time = '$openTime', close_time = '$closeTime'";
      try {
        $row = mysqli_query($db, $sql);
      } catch(\Exception $e) {
        echo json_encode(["message" => "Invalid data", 'status' => 400]); return;
      }
      if ($row) {
        echo json_encode(["response" => "Successfully", 'status' => 200]);
      } else {
        echo json_encode(["message" => "Server of database is error", 'status' => 500]);
      }
    } else {
      echo json_encode(['message' => "Missing some fields", 'status' => 400]);
    }
  }
  public function getBlockUsers() {
    if (!$this->checkAdminRole()) {
      echo json_encode(["message" => "Invalid action. You are not admin", 'status' => 403]);
      return;
    }
    $list = [];
    $db = Db::getInstance();
    $sql = 'SELECT * FROM black_list';
    $result = mysqli_query($db, $sql);
    if ($result->num_rows > 0) {
      while ($row = mysqli_fetch_assoc($result)) {
        $list[] = $row['email'];
      }
    } 
    echo json_encode(['response' => $list, 'status' => 200]);
  }
  public function blockUser() {
    if (!$this->checkAdminRole()) {
      echo json_encode(["message" => "Invalid action. You are not admin", 'status' => 403]);
      return;
    }
    $payload = ['email'];
    $formValid = new FormMiddleware();
    $check = $formValid->checkFullFields($payload);
    if ($check) {
      $email = $_POST['email'];
      $db = Db::getInstance();
      $sql = "insert into black_list values('$email')";
      $row = mysqli_query($db, $sql);
      if ($row) {
        echo json_encode(["message" => "Successfully!", 'status' => 200]);
      }
      else {
        echo json_encode(["message" => "Server of database is error", 'status' => 500]);
      }
    }
    else {
      echo json_encode(['message' => "Missing some fields", 'status' => 400]);
    }
  }

  public function unblockUser() {
    if (!$this->checkAdminRole()) {
      echo json_encode(["message" => "Invalid action. You are not admin", 'status' => 403]);
      return;
    }
    $payload = ['email'];
    $formValid = new FormMiddleware();
    $check = $formValid->checkFullFields($payload);
    if ($check) {
      $email = $_POST['email'];
      $db = Db::getInstance();
      $sql = "delete from black_list where email = '$email'";
      $row = mysqli_query($db, $sql);
      if ($row) {
        echo json_encode(["message" => "Successfully!", 'status' => 200]);
      }
      else {
        echo json_encode(["message" => "Server of database is error", 'status' => 500]);
      }
    }
    else {
      echo json_encode(['message' => "Missing some fields", 'status' => 400]);
    }
  }



  public function checkAdminRole()
  {
    $authMiddleware = new AuthMiddleware();
    $user_valid = $authMiddleware->isJWTValid();

    if ($user_valid == FALSE || json_decode($user_valid)->manager == 0) {
      return FALSE;
    } else {
      return $user_valid;
    }
  }

  public function createDish()
  {
    if (!$this->checkAdminRole()) {
      echo json_encode(["message" => "Invalid action. You are not admin", 'status' => 403]);
      return;
    }

    $payload = ['name', 'description', 'image', 'price'];
    $formValid = new FormMiddleware();
    $check = $formValid->checkFullFields($payload);

    if ($check) {
      $name = $_POST['name'];
      $description = $_POST['description'];
      $image = $_POST['image'];
      $price = $_POST['price'];
      $db = Db::getInstance();
      $sql = "Insert into dish (name,description,image,price) Values ('$name','$description','$image', $price)";
      try {
        $row = mysqli_query($db, $sql);
      } catch(\Exception $e) {
        echo json_encode(["message" => "Invalid data", 'status' => 400]); return;
      }
      $id = mysqli_insert_id($db);

      if ($row) {
        $dish = new dish_model($id, $name, $description, $image, $price);
        echo json_encode(["response" => $dish, 'status' => 200]);
      } else {
        echo json_encode(["message" => "Server of database is error", 'status' => 500]);
      }
    } else {
      echo json_encode(['message' => "Missing some fields", 'status' => 400]);
    }
  }

  public function updateDish($param)
  {

    if (!$this->checkAdminRole()) {
      echo json_encode(["message" => "Invalid action. You are not admin", 'status' => 403]);
      return;
    }
    $payload = ['name', 'description', 'image', 'price'];
    $formValid = new FormMiddleware();
    $check = $formValid->checkFullFields($payload);
    if ($check) {
      $id = substr($param, 1, -1);
      $db = Db::getInstance();
      $sql = "select * from dish where id = $id";
      try {
        $row = mysqli_query($db, $sql);
      } catch(\Exception $e) {
        echo json_encode(["message" => "Invalid data", 'status' => 400]);
        return;
      }
      if ($row->num_rows > 0) {
        $name = $_POST['name'];
        $description = $_POST['description'];
        $image = $_POST['image'];
        $price = $_POST['price'];

        $sql = "Update dish set name = '$name', description = '$description', image = '$image', price = $price where id = $id";

        $row = mysqli_query($db, $sql);
        if ($row) {
          $dish = new dish_model($id, $name, $description, $image, $price);
          echo json_encode(["response" => $dish, 'status' => 200]);
        } else {
          echo json_encode(["message" => "Server of database is error", 'status' => 500]);
        }
      } else {
        echo json_encode(["message" => "Dish $id not found", 'status' => 404]);
      }
    } else {
      echo json_encode(['message' => "Missing some fields", 'status' => 400]);
    }
  }

  public function deleteDish($param)
  {
    if (!$this->checkAdminRole()) {
      echo json_encode(["message" => "Invalid action. You are not admin", 'status' => 403]);
      return;
    }

    $id = substr($param, 1, -1);

    $db = Db::getInstance();
    $sql = "SELECT * FROM dish WHERE id =$id";
    $row = mysqli_query($db, $sql);
    if ($row->num_rows == 0) {
      echo json_encode(["message" => "Dish not found", 'status' => 404]);
      return;
    }

    $sql = "delete from dish where id = $id";
    $row = mysqli_query($db, $sql);

    if ($row) {
      echo json_encode(["response" => "Successfully!", 'status' => 200]);
    } else {
      echo json_encode(["message" => "Server of database is error", 'status' => 500]);
    }
  }

  public function createBlog()
  {
    if (!$this->checkAdminRole()) {
      echo json_encode(["message" => "Invalid action. You are not admin", 'status' => 403]);
      return;
    }

    $payload = ['title', 'content', 'image'];
    $formValid = new FormMiddleware();
    $check = $formValid->checkFullFields($payload);

    if ($check) {
      $title = $_POST['title'];
      $content = $_POST['content'];
      $image = $_POST['image'];
      $date = time();

      $db = Db::getInstance();
      $sql = "insert into blog(title, content, image) values ('$title','$content', '$image')";
      try {
        $row = mysqli_query($db, $sql);
      } catch(\Exception $e) {
        echo json_encode(["message" => "Invalid data", 'status' => 400]); return;
      }
      $id = mysqli_insert_id($db);
      if ($row) {
        $blog = new blog_model($id, $title, $content, $image, $date);
        echo json_encode(['response' => $blog, 'status' => 200]);
      } else {
        echo json_encode(['message' => 'Server of database is error', 'status' => 500]);
      }
    } else {
      echo json_encode(['message' => "Missing some fields", 'status' => 400]);
    }
  }

  public function deleteBlog($param)
  {
    if (!$this->checkAdminRole()) {
      echo json_encode(["message" => "Invalid action. You are not admin", 'status' => 403]);
      return;
    }

    $id = substr($param, 1, -1);

    $db = Db::getInstance();
    $sql = "SELECT * FROM blog WHERE id =$id";
    $row = mysqli_query($db, $sql);
    if ($row->num_rows == 0) {
      echo json_encode(["message" => "Blog $id not found", 'status' => 404]);
      return;
    }

    $sql = "delete from comment where blogId = $id";
    $row = mysqli_query($db, $sql);

    if ($row) {
      $sql = "delete from blog where id = $id";
      $row = mysqli_query($db, $sql);
      if ($row) {
        echo json_encode(["response" => "Successfully!", 'status' => 200]);
      } else {
        echo json_encode(["message" => "Server of database is error", 'status' => 500]);
      }
    } else {
      echo json_encode(["message" => "Server of database is error", 'status' => 500]);
    }
  }

  public function updateBlog($param)
  {
    if (!$this->checkAdminRole()) {
      echo json_encode(["message" => "Invalid action. You are not admin", 'status' => 403]);
      return;
    }

    $payload = ['title', 'content', 'image'];
    $formValid = new FormMiddleware();
    $check = $formValid->checkFullFields($payload);

    if ($check) {
      $id = substr($param, 1, -1);

      $db = Db::getInstance();
      $sql = "select * from blog where id = $id";
      try {
        $row = mysqli_query($db, $sql);
      } catch(\Exception $e) {
        echo json_encode(["message" => "Invalid data", 'status' => 400]); return;
      }
      if ($row->num_rows > 0) {
        $title = $_POST['title'];
        $content = $_POST['content'];
        $image = $_POST['image'];
        $date = time();

        $sql = "update blog set title = '$title', content = '$content', image = '$image' where id = $id";
        $row = mysqli_query($db, $sql);
        if ($row) {
          $sql = "SELECT * from comment where blogId = $id";
          $result = mysqli_query($db, $sql);
          if ($result) {
            $list = [];
            while ($row = mysqli_fetch_assoc($result)) {
              $list[] = new comment_model($row['id'], $row['userId'], $row['blogId'], $row['description']);
            }

            $blog = new blog_model($id, $title, $content, $image, $date);
            echo json_encode(['response' => ["blog" => $blog, "comments" => $list], 'status' => 200]);
          } else {
            echo json_encode(['message' => 'Server of database is error', 'status' => 500]);
          }
        } else {
          echo json_encode(['message' => 'Server of database is error', 'status' => 500]);
        }
      } else {
        echo json_encode(['message' => "Blog $id not found", 'status' => 404]);
      }
    } else {
      echo json_encode(['message' => "Missing some fields", 'status' => 400]);
    }
  }

  public function getAllUsers()
  {
    if (!$this->checkAdminRole()) {
      echo json_encode(["message" => "Invalid action. You are not admin", 'status' => 403]);
      return;
    }

    $db = Db::getInstance();
    $sql = "SELECT * FROM user";
    $list = [];

    $result = mysqli_query($db, $sql);
    if ($result && $result->num_rows) {
      while ($row = mysqli_fetch_assoc($result)) {
        $list[] = new user_model($row['id'], $row['email'], '', $row['username'], $row['phoneNumber'], $row['avatar'], $row['manager']);
      }
      echo json_encode(["response" => $list, 'status' => 200]);
    } else {
      echo json_encode(["message" => "No user found || Server of database is error", 'status' => 500]);
    }
  }

  public function deleteUser($param)
  {
    $user_valid = $this->checkAdminRole();
    if (!$user_valid) {
      echo json_encode(["message" => "Invalid action. You are not admin", 'status' => 403]);
      return;
    }

    $id = substr($param, 1, -1);
    $admin_id = json_decode($user_valid)->id;

    if ($id == $admin_id) {
      echo json_encode(["message" => "You can't delete yourself!", "status" => 403]);
      return;
    }

    $db = Db::getInstance();
    $sql = "select * from user where id = $id";
    $row = mysqli_query($db, $sql);
    if ($row->num_rows > 0) {
      // Delete all comment
      $sql = "delete from comment where userId = $id";
      $row = mysqli_query($db, $sql);
      if ($row == TRUE) {
        // Delete user
        $sql = "delete from user where id = $id";
        $row = mysqli_query($db, $sql);

        if ($row == TRUE) {
          echo json_encode(["response" => "Successfully!", "status" => 200]);
        } else {
          echo json_encode(['message' => 'Server of database is error', 'status' => 500]);
        }
      } else {
        echo json_encode(['message' => 'Server of database is error', 'status' => 500]);
      }
    } else {
      echo json_encode(["message" => "User $id not found", 'status' => 404]);
    }
  }

  public function deleteComment($param)
  {
    if (!$this->checkAdminRole()) {
      echo json_encode(["message" => "Invalid action. You are not admin", 'status' => 403]);
      return;
    }

    $comment_id = substr($param, 1, -1);

    $db = Db::getInstance();
    $sql = "select * from comment where id = $comment_id";
    $row = mysqli_query($db, $sql);
    if ($row->num_rows > 0) {
      $sql = "delete from comment where id = $comment_id";
      $row = mysqli_query($db, $sql);
      if ($row == TRUE) {
        echo json_encode(["response" => "Successfully!", "status" => 200]);
      } else {
        echo json_encode(['message' => 'Server of database is error', 'status' => 500]);
      }
    } else {
      echo json_encode(["message" => "Comment $comment_id not found", 'status' => 404]);
    }
  }
  public function getAllReservations() {
    if (!$this->checkAdminRole()) {
      echo json_encode(["message" => "Invalid action. You are not admin", 'status' => 403]);
      return;
    }
    $db = Db::getInstance();
    $sql = "select * from reservation";
    try {
      $result = mysqli_query($db, $sql);
    } catch(\Exception $e) {
      echo json_encode(["message" => "Invalid data", 'status' => 400]); return;
    }
    if ($result->num_rows > 0) {
      while ($row = mysqli_fetch_assoc($result)) {
        $list[] = new reservation_model($row['id'], $row['name'], $row['email'], $row['phoneNumber'], $row['NoP'], $row['date'], $row['time'], $row['description']);
      }
    } 
    echo json_encode(['response' => $list, 'status' => 200]);
  }
}
