<?php
    $db_host = "localhost";
    $db_username = "root";
    $db_password = "";
    $db_name = "board";

    $db_link = new mysqli($db_host,$db_username,$db_password,$db_name);

    if($db_link){
        // echo "success";
        $db_link -> query("SET NAMES utf8");
    }
    /*****************讀取留言板******************/
    $toSqlGetData = "SELECT * FROM msg";
    $allData = $db_link -> query($toSqlGetData);
    
    $alllData_Num = $allData->num_rows;
    $nowPage = 1;
    if(isset($_GET["page"])){
        $nowPage = $_GET["page"];
    }
    $showRow = 2;
    $getStartRow = ($nowPage - 1)*$showRow;
    $totalPage = ceil($alllData_Num/$showRow);

    $toSqlGetData_limit = $toSqlGetData." LIMIT {$getStartRow}, {$showRow}";
    $limitData = $db_link->query($toSqlGetData_limit);
    
    /*****************檢測是否有登入******************/
    session_start();
    $isLogin = false;
    if(!isset($_SESSION["loginMember"]) || ($_SESSION["loginMember"] == "") ){        
    }else{
        $isLogin =  true;
    };    
    /*****************登入測試帳號密碼******************/
    if(isset($_POST["loggin"])){
        // echo "有登入資訊送來"; 
        $toSqlGetAdmin = "SELECT * FROM admin";
        $admin = $db_link->query( $toSqlGetAdmin);
        $admin = $admin->fetch_assoc();
        if(($admin["username"] == $_POST["user"]) && ($admin["passwd"] == $_POST["pw"])){
            // echo "登入成功";
            $_SESSION["loginMember"] = $_POST["user"];
            header("Location: board.php");
        }else{
            echo "登入失敗";
        };      
    }
    /*****************登出******************/
    if(isset($_GET["loginout"])){
        unset($_SESSION["loginMember"]);
        header("Location: board.php");
    }
    /*****************刪除資料******************/
    if(isset($_GET["delete"])){
        $deleteId = $_GET["delete"];
        $toSqlDel = "DELETE FROM msg WHERE id = $deleteId";
        $stmt = $db_link->query($toSqlDel);       
        header("Location: board.php?page={$nowPage}");
    }
    /*****************更新資料******************/
    if(isset($_POST["update"])){
        $id = $_POST["update"];
        $toSqlUpdate = "UPDATE msg SET `name`=?,title=?,content=? WHERE id={$id}";
        $stmt = $db_link->prepare($toSqlUpdate);
        $stmt->bind_param("sss",
            $_POST["name"],
            $_POST["title"],
            $_POST["content"]
        );
        $stmt->execute();
        $stmt->close();
        header("Location: board.php?page={$nowPage}");
    }
    /*****************新增資料****************/
    if(isset($_POST["add"])){
        $id = $_POST["add"];
        $toSqlAdd = "INSERT INTO msg (`name`,title,content,gender,web,mail,`time`) VALUES (?,?,?,?,?,?,NOW())";
        $stmt = $db_link->prepare($toSqlAdd);
        $stmt->bind_param("ssssss",
            $_POST["name"],
            $_POST["title"],
            $_POST["content"],
            $_POST["gender"],
            $_POST["web"],
            $_POST["mail"]
        );
        $stmt->execute();
        $stmt->close();
        header("Location: board.php");
    }
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
    <style>
        * { box-sizing:border-box;}
        .msg { margin:0 0 20px 0; padding:0 0 10px 0; border-bottom:1px solid #ededed;}
        /* .msg p { margin:0; padding:0;font:15px/1.5 "微軟正黑體"}; */
        #editBoard { display:none; padding:20px; border:1px solid black;}
        #editBoard.on { display:block;}
        #editBoard h3 {font-size:20px; font-weight:bold;}
        #addBoard { display:block; padding:20px; border:1px solid black;}
        #addBoard h3 {font-size:20px; font-weight:bold;}
    </style>
</head>
<body>
    <?php while($item = $limitData->fetch_assoc()) {?>
        <div class="msg">
            <p>姓名: <?php echo $item["name"]; ?></p>
            <p>標題: <?php echo $item["title"]; ?></p>
            <p>內容: <?php echo $item["content"]; ?></p>
            <?php if($isLogin == true) { ?>
                <a href="?page=<?php echo $nowPage; ?>&delete=<?php echo $item["id"]; ?>">刪除</a>
            <?php } ?>
            <?php if($isLogin == true){ ?>
                <a href="javascript:void(0)" onclick="hello(<?php echo $item['id']; ?>,this)"
                data-temp="<?php echo $item["name"].",".$item["title"].",".$item["content"]; ?>">更新</a>
            <?php } ?>
        </div>
    <?php } ?>

    <?php if($nowPage > 1) {?>
        <a href="?page=<?php echo $nowPage - 1; ?>">上一頁</a>
    <?php } ?>
    <?php if($nowPage < $totalPage) {?>
        <a href="?page=<?php echo $nowPage + 1; ?>">下一頁</a>
    <?php } ?>

    <?php if ($isLogin == false) {?>
        <br><br>
        <form action="" method="post">
            <input type="hidden" name="loggin">
            <p>帳號：<input type="text" name="user"></p>
            <p>密碼：<input type="text" name="pw"></p>
            <input type="submit" value="登入">
            <small>帳號/密碼:admin/admin <br></small>
            <small>登入後，可以刪除或者修改留言。</small>
        </form>
        <br><br>
    <?php } ?>
    
    <?php if($isLogin == true){ ?>
        <br><br>
        <div id="editBoard">
            <p><h3>編輯</h3></p>
            <form action="" method="post">
                <input type="hidden" name="update" id="update" value="00">
                <p>姓名：<input type="text" name="name" id="name" value=""></p>
                <p>標題：<input type="text" name="title" id="title" value=""></p>
                <p>內容：<input type="text" name="content" id="content" value=""></p>
                <input type="submit" value="送出">
            </form>
        </div>
        <br><br>
    <?php } ?>

    <div id="addBoard">
        <p><h3>新增留言</h3></p>
        <form action="" method="post">
            <input type="hidden" name="add" >
            <p>姓名：<input type="text" name="name" value=""></p>
            <p>標題：<input type="text" name="title"  value=""></p>
            <p>內容：<input type="text" name="content" value=""></p>
            <p>性別：<input type="radio" name="gender" value="男" checked>男生/
            <input type="radio" name="gender" value="女">女生
            </p>
            <p>個人網站：<input type="text" name="web" value=""></p>
            <p>電子信箱：<input type="text" name="mail" value=""></p>
            <input type="submit" value="送出">
        </form>
    </div>

    <?php if ($isLogin == true) {?>
        <br><br>
        <a href="?loginout">登出</a>
    <?php } ?>

    <script>
        function hello(num,$this){            
            document.querySelector("#update").value = num ;
            let temp = $this.dataset.temp.split(",");          
            document.querySelector("#name").value = temp[0];
            document.querySelector("#title").value = temp[1];
            document.querySelector("#content").value = temp[2];  
            document.querySelector("#editBoard").classList.add("on");
        };
    </script>
</body>
</html>