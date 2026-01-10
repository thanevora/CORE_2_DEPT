<?php
session_start();


?>

<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet" />
     <title>Order | Soliera Restaurant</title>
    <link href="https://cdn.jsdelivr.net/npm/daisyui@3.9.4/dist/full.css" rel="stylesheet" type="text/css" />
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css"/>
    <link rel="stylesheet" href="../CSS/sidebar.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
        <link rel="stylesheet" href="../CSS/calendar.css">

    <title>Soliera Hotel</title>
    
    <style>
        * {
            scroll-behavior: smooth
        }
       
        .text-outline {
            text-shadow: 1px 1px 3px rgba(0,0,0,0.3);
        }
        
        /* Initial transparent nav */
        .navbar {
            transition: all 0.3s ease;
        }
        
        /* Scrolled state */
        .navbar.scrolled {
            background-color: #001f54 !important;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .navbar.scrolled .btn-ghost {
            color: white !important;
        }
        
        .navbar.scrolled .menu-horizontal a {
            color: white !important;
        }
    </style>
</head>

<body>
    <!-- Navigation -->
    <?php include('landing_page/nav.php'); ?>
    
    <!-- Hero Section -->
    <?php include('landing_page/hero.php'); ?>

    <!-- Rooms Section -->
    <?php include('landing_page/room.php'); ?>

    <!-- Restaurant Section -->
    <?php include('landing_page/restaurant.php'); ?>

    <!-- Hotel Section -->
    <?php include('landing_page/hotel.php'); ?>

    <!-- Amenities Section -->
    <?php include('landing_page/ameneties.php'); ?>

    <!-- Testimonials -->
    <?php include('landing_page/testimonials.php'); ?>

    <!-- Contacts -->
    <?php include('landing_page/contacts.php'); ?>

    <!-- Footer -->
    <?php include('landing_page/footer.php'); ?>

    

    
    <script>
        window.addEventListener('scroll', function() {
            const navbar = document.getElementById('mainNav');
            const heroHeight = document.querySelector('.hero').offsetHeight;
            
            if (window.scrollY > heroHeight * 0.8) {
                navbar.classList.add('scrolled');
            } else {
                navbar.classList.remove('scrolled');
            }
        });
    </script>

    <script src="https://cdn.jsdelivr.net/npm/aos@2.3.4/dist/aos.js"></script>
    <link href="https://cdn.jsdelivr.net/npm/aos@2.3.4/dist/aos.css" rel="stylesheet">
    <script>
        AOS.init({
            duration: 1000,
            once: true
        });
    </script>
</body>
</html>