<!DOCTYPE html>
<html>
    <head>
        <meta charset="utf-8">
        <meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1">
        <title>Tiny Server - Setup</title>
        <link rel="icon" type="image/x-icon" href="img/favicon.ico">

        <meta name="description" content="">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <link rel="stylesheet" href="css/bootstrap.min.css">
        <link rel="stylesheet" href="css/bootstrap-theme.min.css">
        <link rel="stylesheet" href="css/fontAwesome.css">
        <link rel="stylesheet" href="css/light-box.css">
        <link rel="stylesheet" href="css/owl-carousel.css">
        <link rel="stylesheet" href="css/templatemo-style.css">

        <link href="https://fonts.googleapis.com/css?family=Open+Sans:300,400,600,700,800" rel="stylesheet">

        <script src="js/vendor/modernizr-2.8.3-respond-1.4.2.min.js"></script>
        <script src="https://ajax.googleapis.com/ajax/libs/jquery/2.1.1/jquery.min.js"></script>
        <script src="https://ajax.googleapis.com/ajax/libs/jquery/2.1.1/jquery.js"></script>
        <script language="javascript" type="text/javascript"> function windowClose() { window.open('','_parent',''); window.close(); } </script>

        <style>

            p { padding: 0px 10px 0px 10px;   font-size: medium; }
            hr { margin-top: 5px; margin-bottom: 5px; }

            .bigbutton
            {
                width:auto;
                color: rgba(255, 255, 255, 0.7);
                background-color: rgba(20, 20, 20, 1.0);
                font-size: large;
                padding: 5px 10px 5px 10px;
                margin: 5px 0px 5px 0px;
                border: 5px;
                border-color: rgba(255, 255, 255, 1.0);
                cursor: pointer;
                border-radius: 12px;
                opacity: 0.9;
                transition: transform .0s ease-out;
                box-shadow: 3px 3px 2px rgba(0,0,0,0.3);
            }
            .bigbutton:hover
            {
                color: rgba(255, 255, 255, 1.0);
                background-color: rgba(20, 20, 20, 1.0);
                box-shadow: 3px 3px 2px rgba(0,0,0,0.5);
                transform: translate(-2px, -1px);
            }

            .knob
            {
                width:auto;
                vertical-align: middle;
                color: rgba(255, 255, 255, 0.7);
                background-color: rgba(30, 30, 30, 1.0);
                font-size: large;
                padding: 5px 10px 5px 10px;
                margin: 5px 0px 5px 0px;
                border: 5px;
                border-color: rgba(255, 255, 255, 1.0);
                cursor: pointer;
                border-radius: 12px;
                opacity: 0.9;
                transition: transform .0s ease-out;
                box-shadow: 3px 3px 2px rgba(0,0,0,0.3);
            }
            .knob:hover
            {
                color: rgba(255, 255, 255, 1.0);
                background-color: rgba(30, 30, 30, 1.0);
                box-shadow: 3px 3px 2px rgba(0,0,0,0.5);
                transform: translate(-2px, -1px);
            }


            .cancelbtn { width: auto;padding: 10px 18px;background-color: #f44336; }
            .imgcontainer {text-align: center;margin: 24px 0 12px 0;}
            img.avatar {width: 40%;border-radius: 50%;}
            .container {padding: 16px;}

            span.psw { float: right;padding-top: 16px; }

            /* Change styles for span and cancel button on extra small screens */
            @media screen and (max-width: 300px)
            {
                span.psw {display: block;float: none;}
                .cancelbtn {width: 100%;}
            }

            .border
            {
                border-radius: 12px;
                border-width: thin;
                border-style: solid;
                padding: 5px 10px 5px 10px;
                color: rgba(155, 155, 155, 1.0); border-color: rgba(255, 255, 255, 1.0);
            }

            .border:hover
            {
                color: white;
                text-decoration: none;
/*                background: rgba(0, 0, 0, 0.2);*/
            }

/* ============================================================================= */

        </style>
    </head>
    
    <body>







        <header class="nav-down responsive-nav hidden-lg hidden-md">
            <button type="button" id="nav-toggle" class="navbar-toggle" data-toggle="collapse" data-target="#main-nav">
                <span class="sr-only">Toggle navigation</span>
                <span class="icon-bar"></span>
                <span class="icon-bar"></span>
                <span class="icon-bar"></span>
            </button>
            <!--/.navbar-header-->
            <div id="main-nav" class="collapse navbar-collapse">
                <!-- <nav>
                    <ul class="nav navbar-nav">
                        <li><a href="#licenseserver">Overview</a></li>
                        <li><a href="#createlicense">Set Password</a></li>
                        <li><a href="#updatelicense">Setup Network</a></li>
                        <li><a href="#deletelicense">Setup Domain</a></li>
                        <li><a href="#selectlicense">Setup TLS Certificates</a></li>
                        <li><a href="#servicemanager">Test Tiny Server</a></li>
                        <li><a href="#usesystem">Use Tiny Server</a></li>
                    </ul>
                </nav> -->
            </div>
        </header>

        <div class="sidebar-navigation hidde-sm hidden-xs">
            <div class="logo" style="border: 1px solid rgba(250,250,250,0.5);">
                <p style="display: flex; vertical-align: middle; text-align: center;">
                    <a style="border: none; height: 1rem;" href="index.php">Tiny <em>Server</em></a>
                </p>
            </div>
            <!-- <nav>
                <ul>
                    <li>
                        <a href="#licenseserver">
                            <span class="rect"></span>
                            <span class="circle"></span>
                            Overview
                        </a>
                    </li>
                    <li>
                        <a href="#createlicense">
                            <span class="rect"></span>
                            <span class="circle"></span>
                            Set Password
                        </a>
                    </li>
                    <li>
                        <a href="#updatelicense">
                            <span class="rect"></span>
                            <span class="circle"></span>
                            Public Access
                        </a>
                    </li>
                    <li>
                        <a href="#deletelicense">
                            <span class="rect"></span>
                            <span class="circle"></span>
                            Setup Domain
                        </a>
                    </li>
                    <li>
                        <a href="#selectlicense">
                            <span class="rect"></span>
                            <span class="circle"></span>
                            Setup TLS Certificates
                        </a>
                    </li>
                    <li>
                        <a href="#servicemanager">
                            <span class="rect"></span>
                            <span class="circle"></span>
                            Test Tiny Server
                        </a>
                    </li>
                    <li>
                        <a href="#usesystem">
                            <span class="rect"></span>
                            <span class="circle"></span>
                            Use Tiny Server
                        </a>
                    </li>
                </ul>
            </nav> -->
            <ul class="social-icons">

            <p>
                <div style="background: rgb(40,40,40); color: grey; padding: 0px 0px 0px 0px;">

                </div>
            </p>

                <!-- <li><a href="#"><i class="fa fa-facebook"></i></a></li>
                <li><a href="#"><i class="fa fa-twitter"></i></a></li>
                <li><a href="#"><i class="fa fa-linkedin"></i></a></li>
                <li><a href="#"><i class="fa fa-rss"></i></a></li>
                <li><a href="#"><i class="fa fa-behance"></i></a></li> -->
            </ul>
        </div>          
          

        <div class="slider">

            

            <div class="Modern-Slider content-section" id="licenseserver">


                <div class="item item-1">                                

                    <div class="img-fill">
                        <div style="background: linear-gradient(rgba(0, 0, 0, 0.0), rgba(0, 0, 0, 0.8)), url(img/content/topbg.webp); background-size: cover;" class="image"></div>
                        <div style="position: fixed; left: 50%; top: 45%; transform: translate(-50%, -50%); width: 42rem;" class="info">
                            <div style="background: rgba(0, 0, 0, 0.8); margin-top: 10px;" class="border">
                              <h2 style="color: royalblue; margin: 10px 0px 10px 0px; font-weight: 500; text-transform: capitalize;">Setup Item Not Found</h2>
                                <input style="width: 60%; font-size: x-large;" class="bigbutton border" type="button" value="Go Back" onClick="history.go(-1);">
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>


    <script src="https://ajax.googleapis.com/ajax/libs/jquery/1.11.2/jquery.min.js"></script>
    <script>window.jQuery || document.write('<script src="js/vendor/jquery-1.11.2.min.js"><\/script>')</script>

    <script src="js/vendor/bootstrap.min.js"></script>
    
    <script src="js/plugins.js"></script>
    <script src="js/main.js"></script>

    <script>
        // Hide Header on on scroll down
        var didScroll;
        var lastScrollTop = 0;
        var delta = 5;
        var navbarHeight = $('header').outerHeight();

        $(window).scroll(function(event){
            didScroll = true;
        });

        setInterval(function() {
            if (didScroll) {
                hasScrolled();
                didScroll = false;
            }
        }, 250);

        function hasScrolled() {
            var st = $(this).scrollTop();
            
            // Make sure they scroll more than delta
            if(Math.abs(lastScrollTop - st) <= delta)
                return;
            
            // If they scrolled down and are past the navbar, add class .nav-up.
            // This is necessary so you never see what is "behind" the navbar.
            if (st > lastScrollTop && st > navbarHeight){
                // Scroll Down
                $('header').removeClass('nav-down').addClass('nav-up');
            } else {
                // Scroll Up
                if(st + $(window).height() < $(document).height()) {
                    $('header').removeClass('nav-up').addClass('nav-down');
                }
            }
            
            lastScrollTop = st;
        }
    </script>

    <script src="http://ajax.googleapis.com/ajax/libs/jquery/1.10.2/jquery.min.js" type="text/javascript"></script>

</body>
</html>
