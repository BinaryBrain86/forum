<?php 
// Check if the username is set, which indicates the user is logged in
if (isset($userName)): ?>
    <!-- If the user is logged in, show the "My account" link with an icon -->
    <a href="account.php" class="icon-button icon-button-settings">
        <img src="resources/settings.png" alt="Settings Icon">
        <div class="icon-button-settings-tooltip icon-button-tooltip">My account</div>
    </a>
<?php endif; ?>

<!-- Header content section -->
<div class="header-content">
    <div class="header-left">
        <!-- Search form that sends a GET request to search_results.php -->
        <form method="get" action="search_results.php" class="search-form">
            <input type="text" name="search_query" placeholder="Search" required>
            <button type="submit" class="button">Go</button>
        </form>
    </div>
    
    <div class="header-right">
        <?php   
        // Check if the user is logged in
        if (isset($userName)):
            // If the current page is the index page, show the "Create new thread" button
            if(basename($_SERVER['PHP_SELF']) == "index.php"):
                echo "<a href=\"project.php\" class=\"button\">Project</a>";
                echo "<button onclick=\"openModal('threadModal')\" class=\"button\">Create new thread</button>";
            else:
                // If not on the index page, show a "Back to overview" link
                echo "<a href=\"index.php\" class=\"button\">Back to overview</a>";
            endif;

            // Show the link to personal messages
            echo "<a href=\"personalMessages.php\" class=\"button\">PM";
            // If there are unread messages, display the count
            if ($unreadCount > 0):
                echo "<span class=\"unread-count\">" . $unreadCount . "</span>";
            endif;
            echo "</a>";
            
            // If the user is an admin, show the "Administration" link
            if ($roleName == "Admin"):
                echo "<a href=\"admin.php\" class=\"button\">Administration</a>";
            endif;
        
            // Show the logout button with the username
            echo "<a href=\"logout.php\" class=\"button\">Logout "; 
            if (isset($userName)): 
                echo htmlspecialchars($userName); 
            endif; 
            echo "</a>";
        else:
            // If the user is not logged in and not on the index page, show a "Back to overview" link
            if(basename($_SERVER['PHP_SELF']) != "index.php"):
                echo "<a href=\"index.php\" class=\"button\">Back to overview</a>";
            endif;
            // Show the login button
            echo "<button onclick=\"openModal('loginModal')\" class=\"button\">Login</button>";
        endif;
        ?>
    </div>
</div>
