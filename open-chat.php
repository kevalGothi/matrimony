<?php
    session_start();
    include "db/conn.php";
    error_reporting(0);
?>
<?php 

?>
<?php 

?>
<?php 

?>
<?php
    $userN = $_SESSION['username'];
    $psw = $_SESSION['password'];
    if($userN == true && $psw == true){
        $user = mysqli_query($conn,"select * from tbl_user where user_phone = '$userN' and user_pass = '$psw'");
        $fe = mysqli_fetch_array($user);
        $userID = $fe['user_id'];
        if(isset($_POST['chat_send'])){
            $senderid = $_POST['senderid'];
            $receiverid = $_POST['receiverid'];
            $chat_message = $_POST['chat_message'];
            $sqls=mysqli_query($conn,"INSERT INTO tbl_chat(chat_senderID, chat_receiverID, chat_message) values('$senderid','$receiverid','$chat_message')");
            if($sqls){
                echo "<script>alert('Send Chat')</script>";
                echo "<script>window.location.href='see-other-profile.php'</script>";
            }
        }



?>

<!DOCTYPE html>
<html>
<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<title>Chat App</title>
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
	<style type="text/css">

		#container{
			border: 1px solid white;
			height: 540px;
			width: 620px;
			margin-left: 350px;
			background-color: white;
			box-shadow: 5px 5px 10px rgba(0, 0, 0, 0.3);
		}
		#chat{
			border: 1px solid white;
			width: 600px;
			height: 350px;
			margin-left: 0px;
			max-height: 350px;
			overflow: auto; 
			padding: 10px;
		}
		#message{
			margin-left: 20px;
			margin-top: 10px;
		}
		#message_box{
			width: 450px;
			height: 60px;
			background-color: #E4E6EB;
			border-radius: 20px;
			border: 1px solid #E4E6EB;
			padding-left: 20px;
			float: left;
		}
		#send{
			width: 100px;
			height: 60px;
			border-radius: 20px;
			margin-left: 10px;
			border: 1px solid #E4E6EB;
			background-color: #E4E6EB;
			
		}
		#send:hover{
			background-color: blue;
			cursor: pointer;
		}
		#chat_box_message1{
			border: 1px solid #0099FF;
			background-color: #0099FF;
			/*max-width: 120px;*/
			max-width: 30%;
			margin-left: 350px;
            overflow-y: auto;
            border-radius: 20px;
            padding: 20px;
            margin-bottom: 10px;
            height: auto;
            /*box-sizing: border-box;
            width: auto;*/
            color: white;
            margin-top: 20px;

		}
		#chat_box_message2{
			border: 1px solid #E4E6EB;
			background-color: #E4E6EB;
			max-width: 30%;
            overflow-y: auto;
            border-radius: 20px;
            padding: 20px;
            margin-bottom: 10px;
            margin-left: 25px;
            height: auto;
            float: ;
            margin-top: 20px;

		}
		img{
			width: 50px;
            height: 50px;
            border-radius: 50%;
            overflow: hidden;
            float: left;
            margin-left: 10px;
            margin-top: 9px;
		}
		#logout{
			float: left;
			font-weight: bold;
			text-decoration: none;
			float: right;
			margin-right: 30px;
			margin-top: 24px;

		}
		#logout:hover{
			color: red;
		}
		#send_icon:hover{
			cursor: pointer;
		}
	</style>
</head>
<body>
    <?php
    $uuid = $_GET['id'];
        $usefi = mysqli_query($conn,"select * from tbl_user where user_id = '$uuid'");
        $uufe = mysqli_fetch_array($usefi);
    ?>
	<div id="container">
		<div id="" style=""> 
			<img src="upload/<?php echo $uufe['user_img'] ?>">
			 <label style="float: left; margin-left: 10px; margin-top: 27px; font-weight: bold;"><?php echo $uufe['user_name']; ?></label> <a id="logout" href="logout.php">Logout</a><br><br><br>
			<hr>
		 </div>
		 <div id="chat">
		 	
		 		<?php 
		 			$sql1="SELECT chat_senderID, chat_receiverID, chat_message, 
           DATE_FORMAT(chat_date, '%M %e at %l:%i %p') AS time2 
    FROM tbl_chat 
    WHERE 
        (chat_senderID = '$userID' AND chat_receiverID = '$uuid') 
        OR 
        (chat_senderID = '$uuid' AND chat_receiverID = '$userID')
    ORDER BY chat_date ASC";
		 			$query1=mysqli_query($conn,$sql1);

		 			if (mysqli_num_rows($query1) > 0){
		 				while ($row=mysqli_fetch_array($query1)) {
		 				if ($row['chat_receiverID'] == $uuid) {
		 					?>
		 				<div id="chat_box_main1">

		 					<div id="chat_box_message1">
		 					 <?php 
		 						echo $row['chat_message'];
		 					 ?>
		 					</div>
		 					<div style="margin-left: 400px;">
		 						<?php
		 						 echo $row['time2'];
		 						  
		 						?>
		 					</div>
		 			   </div>
 
		 				<?php 
		 				}else{
		 					?>
		 					<div id="chat_box_main2">
		 					<!-- <img style="margin-right: 10px;" src="upload/<?php echo $row['user_img'] ?>">  -->
		 					<div id="chat_box_message2">
		 					 <?php 
		 						echo $row['chat_message'];
		 					  ?>
		 					</div>
		 					  <div style="margin-left: 120px; margin-top: ;">
		 						<?php
		 						 echo $row['time2'];
		 						?>
		 					</div>
		 			       </div>

		 					<?php 
		 				}
		 			}
		 		}
		 		?>
		 	
		 </div>
		 <div id="message">
		 	<form method="POST">
		 		<input id="message_box" type="text" name="message" placeholder="Write message" required>
		 		<input type="text" name="name" value="<?php echo $uuid ?>" hidden>
		 		<input type="text" name="sender" value="<?php echo $userID ?>" hidden>
		 	    <!--input id="send" type="submit" name="send" value="Send"-->
		 	    <button  id="send_icon" type="submit" name="send" style="background: none;border: none;">
		 	    	<img style="width: 70px;height: 57px; float: left; margin-top: 0px;" src="send.png">
		 	    </button>	
		 	</form>
		 	<?php
if (isset($_POST['send'])) {
	$name=$_POST['name'];
	$sender=$_POST['sender'];
	$message=$_POST['message'];

	$sql="INSERT INTO tbl_chat(chat_senderID, chat_receiverID, chat_message) values('$sender','$name','$message')";
	$query=mysqli_query($conn,$sql);


 // Ensure that no further code is executed after the header
}
            ?>
		 </div>
	</div>
</body>
</html>
<?php } ?>