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
    .wed-hom-footer {
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
        /* Updated: Very light gray background */
        background-color: #f8f9fa; 
        /* Updated: Slightly more visible shadow */
        box-shadow: 0 -4px 12px rgba(0, 0, 0, 0.15); 
        z-index: 1000;
        border-top: 1px solid #e2e2e2; /* Adds a clean border line at the top */
    }

    /* 3. Style the links and icons inside the mobile bar */
.mobile-dashboard-nav a {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    flex-grow: 1;
    color: #6c757d; 
    text-decoration: none;
    font-size: 11px;
    transition: color 0.3s ease, transform 0.2s ease, box-shadow 0.3s ease;
    
    /* button-like styles */
    background: #fff; /* or #f9f9f9 for softer */
    padding: 8px 12px;
    border-radius: 10px;
    box-shadow: 0 2px 6px rgba(0,0,0,0.15); /* shadow for button effect */
}

/* Hover/tap effect */
.mobile-dashboard-nav a:hover,
.mobile-dashboard-nav a:active {
    transform: scale(1.05);
    color: #f6af04;
    box-shadow: 0 4px 10px rgba(0,0,0,0.2); /* stronger shadow on hover */
}

    
    .mobile-dashboard-nav a .fa {
        font-size: 20px;
        margin-bottom: 2px; /* Reduced margin for a tighter look */
    }
    
    /* Hover/tap effect for all links */
    .mobile-dashboard-nav a:hover,
    .mobile-dashboard-nav a:active {
        transform: scale(1.05); /* Makes the icon and text slightly larger on hover/tap */
        color: #f6af04; /* Matches the active color on hover */
    }

    /* 4. Style the currently active link/icon */
  /* Style for the currently active link/icon */
.mobile-dashboard-nav a.active {
    color: #00FF00; /* Hot Pink for the text and icon color */
    font-weight: 600; /* Makes the text a bit bolder */
}
/* Give each child a unique bold color */
.mobile-dashboard-nav a:nth-child(1) {
    color: #e63946; /* Red */
    font-weight: bold;
}
.mobile-dashboard-nav a:nth-child(2) {
    color: #457b9d; /* Blue */
    font-weight: bold;
}
.mobile-dashboard-nav a:nth-child(3) {
    color: #da33b0; /* Teal */
    font-weight: bold;
}
.mobile-dashboard-nav a:nth-child(4) {
    color: #f4a261; /* Orange */
    font-weight: bold;
}
.mobile-dashboard-nav a:nth-child(5) {
    color: #9d4edd; /* Purple */
    font-weight: bold;
}


/* Optional: Add a subtle border or background to the active item for more emphasis */
.mobile-dashboard-nav a::after {
    content: '';
    display: block;
    width: 30px; /* Adjust size as needed */
    height: 3px; /* Adjust thickness as needed */
    margin-top: 4px; /* Space between the icon and the line */
    border-radius: 2px;
}
.mobile-dashboard-nav a:nth-child(1).active::after {
    background-color: #e63946; /* Red */
}
.mobile-dashboard-nav a:nth-child(2).active::after {
    background-color: #457b9d; /* Blue */
}
.mobile-dashboard-nav a:nth-child(3).active::after {
    background-color: #da33b0; /* Teal */
}
.mobile-dashboard-nav a:nth-child(4).active::after {
    background-color: #f4a261; /* Orange */
}
.mobile-dashboard-nav a:nth-child(5).active::after {
    background-color: #9d4edd; /* Purple */
}

    /* 5. Add padding to the bottom of the body */
    body {
        padding-bottom: 70px;
    }
    
    .mobile-dashboard-nav {
    display: flex;
    justify-content: space-around;
    align-items: center;
    background: #ffffff;
    padding: 8px 12px;
    box-shadow: 0 -2px 10px rgba(0,0,0,0.1); /* stronger top shadow for nav bar */
    border-top-left-radius: 16px;
    border-top-right-radius: 16px;
}

.mobile-dashboard-nav a {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    text-decoration: none;
    font-size: 11px;
    color: #6c757d;
    flex-grow: 1;

    /* button-like feel */
    background: #fff;
    padding: 6px 10px;
    margin: 0 4px;
    border-radius: 12px;
    box-shadow: 0 2px 6px rgba(0,0,0,0.08);
    transition: all 0.3s ease;
}

.mobile-dashboard-nav a .fa {
    font-size: 20px;
    margin-bottom: 3px;
}

.mobile-dashboard-nav a:hover,
.mobile-dashboard-nav a:active {
    transform: translateY(-2px);
    box-shadow: 0 4px 10px rgba(0,0,0,0.15);
    color: #f6af04;
}

/* Active state */
.mobile-dashboard-nav a.active {
    font-weight: 600;
    color: #6f42c1; /* purple active */
    box-shadow: 0 4px 12px rgba(111, 66, 193, 0.25);
    background: #f9f5ff; /* slight purple tint */
}

.mobile-dashboard-nav a.active::after {
    content: '';
    display: block;
    width: 20px;
    height: 3px;
    background: currentColor; /* matches active color */
    border-radius: 3px;
    margin-top: 4px;
}

}
    </style>
    
    <style>

    </style>