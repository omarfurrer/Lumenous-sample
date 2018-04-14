<!--Main Header--> 
<header class="main-header" role="banner">
    <!--Logo--> 
    <a href="/" class="logo">
        <!--mini logo for sidebar mini 50x50 pixels--> 
        <span class="logo-mini"><b>{{ config('app.name', 'Lumenous')[0] }}</b></span>
        <!--logo for regular state and mobile devices--> 
        <span class="logo-lg"><b>{{ config('app.name', 'Lumenous') }}</b></span>
    </a>

    <!--Header Navbar--> 
    <nav class="navbar navbar-static-top" role="navigation">
        <div class="container">
            <!--Navbar Right Menu--> 
            <!-- Collect the nav links, forms, and other content for toggling -->
            <div class="collapse navbar-collapse" id="navbar-collapse">
                <ul class="nav navbar-nav navbar-right">
                    <li><a href="/about">About</a></li>
                    <li><a href="/contact-us">Contact Us</a></li>
                    <li><a href="/login">Login</a></li>
                    <li><a href="/register">Register</a></li>
                </ul>
            </div>
        </div>
    </nav>
</header>