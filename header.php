<?php if (isset($userName)): ?>
    <a href="account.php" class="icon-button icon-button-settings"><img src="resources/settings.png" alt="Settings Icon"><div class="icon-button-settings-tooltip icon-button-tooltip">My account</div></a>
<?php endif; ?>
    <div class="header-content">
        <div class="header-left">
            <form method="get" action="search_results.php" class="search-form">
                <input type="text" name="search_query" placeholder="Search" required>
                <button type="submit" class="button">Go</button>
            </form>
        </div>
        <div class="header-right">
        <?php   
            if (isset($userName)):
                if(basename($_SERVER['PHP_SELF']) == "index.php"):
                    echo "<button onclick=\"openModal('threadModal')\" class=\"button\">Create new thread</button>";
                else:
                    echo "<a href=\"index.php\" class=\"button\">Back to overview</a>";
                endif;
                echo "<a href=\"personalMessages.php\" class=\"button\">PM";
                if ($unreadCount > 0):
                    echo "<span class=\"unread-count\">" . $unreadCount . "</span>";
                endif;
                echo "</a>";
                
                if ($roleName == "Admin"):
                echo "<a href=\"admin.php\" class=\"button\">Administration</a>";
                endif;
            
                echo "<a href=\"logout.php\" class=\"button\">Logout "; if (isset($userName)): echo htmlspecialchars($userName); endif; echo "</a>";
            else:
                if(basename($_SERVER['PHP_SELF']) != "index.php"):
                    echo "<a href=\"index.php\" class=\"button\">Back to overview</a>";
                endif;
                echo "<button onclick=\"openModal('loginModal')\" class=\"button\">Login</button>";
            endif;
        ?>
        </div>
    </div>