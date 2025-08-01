<?php
    session_start();
    include "../db/conn.php";
    $username = $_SESSION['username'];
    $pass = $_SESSION['pass'];
    $adminsql = mysqli_query($conn, "select * from user where email = '$username' and pass = '$pass'");
    $adminfetch = mysqli_fetch_array($adminsql);
    if($username == true & $pass == true){
?>

<?php include "inc/header.php"; ?>    
    <!-- ?PROD Only: Google Tag Manager (noscript) (Default ThemeSelection: GTM-5DDHKGP, PixInvent: GTM-5J3LMKC) -->
    <noscript><iframe src="https://www.googletagmanager.com/ns.html?id=GTM-5J3LMKC" height="0" width="0" style="display: none; visibility: hidden"></iframe></noscript>
    <!-- End Google Tag Manager (noscript) -->
    
    <!-- Layout wrapper -->
<div class="layout-wrapper layout-content-navbar  ">
  <div class="layout-container">

    <?php include "inc/side_bar.php"; ?>

    <!-- Layout container -->
    <div class="layout-page">

<?php include "inc/top_bar.php"; ?>
      <!-- Content wrapper -->
<div class="content-wrapper">

        <!-- Content -->
        
        <div class="container-xxl flex-grow-1 container-p-y">
            
            
            <div class="app-ecommerce">
            <form method="POST">
              <!-- Add Product -->
              <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center mb-6 row-gap-4">
            
              
            
              <div class="row">
            
                <!-- First column-->
                <div class="col-12 col-lg-12">
                  <!-- Product Information -->
                  <div class="card mb-6">
                    <div class="card-header">
                      <h5 class="card-tile mb-0">Lead information</h5>
                    </div>
                    <div class="card-body">
                      <div class="mb-6">
                        <label class="form-label" for="ecommerce-product-name">Name</label>
                        <input type="text" class="form-control" id="ecommerce-product-name" placeholder="Client Name" name="clientname" aria-label="Product title">
                      </div>
                      <div class="row mb-6">
                        <div class="col"><label class="form-label" for="ecommerce-product-sku">Number</label>
                          <input type="number" class="form-control" id="ecommerce-product-sku" placeholder="Mobile Number" name="number" ></div>
                        <div class="col"><label class="form-label" for="ecommerce-product-barcode">Enquery Details</label>
                          <input type="text" class="form-control" id="ecommerce-product-barcode" placeholder="Enquery Details" name="enquerydetails"></div>
                      </div>
                      <!-- Description -->
                      <div>
                        <label class="mb-1">Description </label>
                        
                        <textarea class="form-control p-0" name="note" id=""></textarea>
                          
                      </div><br>
                      <div>
                        <label class="mb-1">Assign User </label>
                        
                        <select class="form-control p-0" name="assign" id="">
                          <option>Select User</option>
                          <?php
                            $ussql = mysqli_query($conn, "select * from user where status = 'User'");
                            foreach($ussql as $data){
                                ?>
                                <option value="<?php echo $data['id'] ?>"><?php echo $data['name'] ?></option>
                                <?php
                            }
                          ?>
                          </select>
                          
                      </div><br>
                      <div class="mb-6">
                        <label class="form-label" for="ecommerce-product-name">Follow Up Date</label>
                        <input type="date" class="form-control" id="ecommerce-product-name" placeholder="Client Name" name="followdate" aria-label="Product title">
                      </div>
                      <br><br>
                      <div class="col" >
                          <input type="submit" class="btn btn-primary" value="Submit"  name="submit" style="background:#7367f0;border-radius:40px;boder:none;">
                      </div>
                    </div>
                  </div>
                  <!-- /Product Information -->

                </div>
                <!-- /Second column -->
            
                
              </div>
            </div>
            </form>
          </div>
<?php
  if(isset($_POST['submit'])){
    $clientname = $_POST['clientname'];
    $number = $_POST['number'];
    $enquerydetails = $_POST['enquerydetails'];
    $assign = $_POST['assign'];
    $note = $_POST['note'];
    $followdate = $_POST['followdate'];
    $date = date("Y-m-d");

    $inssql = mysqli_query($conn, "insert into leads (clientname,number,enquerydetails,assign,status,createdate,note,followupdate) value ('$clientname' , '$number' , '$enquerydetails' , '$assign' , '1' , '$date' , '$note' , '$followdate')");
    if($inssql){
      echo "<script>alert('Successfully Add')</script>";
      
    }
  }
?>
        <!-- / Content -->

<?php include "inc/footer.php"; ?>

<?php
    }else{
        echo "<script>window.location.href='login/';</script>";
    }
?>