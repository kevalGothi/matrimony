<!doctype html>
<html lang="en">

<head>
    <title>Wedding Matrimony</title>
    <!--== META TAGS ==-->
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">
    <meta name="theme-color" content="#f6af04">
    <meta name="description" content="">
    <meta name="keyword" content="">
    <!--== FAV ICON(BROWSER TAB ICON) ==-->
    <link rel="shortcut icon" href="images/fav.ico" type="image/x-icon">
    <!--== CSS FILES ==-->
    <link rel="stylesheet" href="css/bootstrap.css">
    <link rel="stylesheet" href="css/font-awesome.min.css">
    <link rel="stylesheet" href="css/animate.min.css">
    <link rel="stylesheet" href="css/style.css">
    <!-- Add this just before </head> -->
    <script src="//cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
       
    
/* --- By default, hide the mobile navigation on desktop --- */
    .mobile-dashboard-nav {
    display: none;
}

/* --- STYLES FOR MOBILE SCREENS (Screens less than 768px wide) --- */
    @media (max-width: 767.98px) {
    
    /* 1. Hide the original desktop sidebar */
    .db-nav {
        display: none !important; 
    }

    /* 2. Style and display the new mobile bottom bar */
    .mobile-dashboard-nav {
        display: flex;
        justify-content: space-around;
        align-items: center;
        position: fixed;
        bottom: 0;
        left: 0;
        right: 0;
        height: 60px;
        background-color: #ffffff;
        box-shadow: 0 -2px 10px rgba(0, 0, 0, 0.1);
        z-index: 1000; /* Ensures it's on top of other content */
    }

    /* 3. Style the links and icons inside the mobile bar */
    .mobile-dashboard-nav a {
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        flex-grow: 1;
        color: #888; /* Inactive icon color */
        text-decoration: none;
        font-size: 12px;
    }

    .mobile-dashboard-nav a .fa {
        font-size: 20px;
        margin-bottom: 4px;
    }
    
    /* 4. Style the currently active link/icon */
    .mobile-dashboard-nav a.active {
        color: #f6af04; /* Your theme's primary color */
    }

    /* 5. Add padding to the bottom of the body to prevent content
       from being hidden behind the new fixed navigation bar */
    body {
        padding-bottom: 70px; 
    }
}
    </style>