<?php
/**
 * Icons Slider - Cleanup Script
 * 
 * Run this script to safely delete ALL icon slides from your site.
 * Paste the URL http://yoursite.com/wp-content/plugins/icons-slider/cleanup.php in your browser
 * and confirm the action.
 * 
 * Only administrators can use this script.
 */

// Load WordPress
require_once( '../../../../wp-load.php' );

// Check if user is logged in and is an administrator
if ( ! is_user_logged_in() || ! current_user_can( 'manage_options' ) ) {
    wp_die( 'Unauthorized access. You must be logged in as an administrator.' );
}

// Check for confirmation parameter
if ( ! isset( $_GET['confirm'] ) || $_GET['confirm'] !== 'yes' ) {
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <title>Icons Slider - Cleanup</title>
        <style>
            body { font-family: Arial, sans-serif; padding: 40px; background: #f1f1f1; }
            .container { max-width: 500px; margin: 0 auto; background: white; padding: 30px; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,.1); }
            h1 { color: #333; }
            p { color: #666; line-height: 1.6; }
            .warning { background: #fff3cd; padding: 15px; border-radius: 4px; margin: 20px 0; border-left: 4px solid #ffc107; }
            .button { display: inline-block; padding: 10px 20px; margin: 5px 5px 5px 0; border-radius: 4px; text-decoration: none; font-weight: bold; }
            .btn-danger { background: #dc3545; color: white; }
            .btn-danger:hover { background: #c82333; }
            .btn-secondary { background: #6c757d; color: white; }
            .btn-secondary:hover { background: #5a6268; }
        </style>
    </head>
    <body>
        <div class="container">
            <h1>⚠️ Icons Slider - Cleanup</h1>
            <p>This tool will <strong>permanently delete ALL icon slides</strong> from your website.</p>
            
            <div class="warning">
                <strong>Warning:</strong> This action cannot be undone. All icon slides and their data will be removed from the database.
            </div>
            
            <p>Are you sure you want to proceed?</p>
            
            <form method="GET" style="margin-top: 30px;">
                <a href="?confirm=yes" class="button btn-danger" onclick="return confirm('Are you absolutely sure? This will delete ALL icon slides permanently.');">Yes, Delete All Slides</a>
                <a href="<?php echo admin_url(); ?>" class="button btn-secondary;">Cancel</a>
            </form>
        </div>
    </body>
    </html>
    <?php
    exit;
}

// Perform the deletion
$deleted_count = 0;
$args = array(
    'post_type'      => 'icon_slide',
    'posts_per_page' => -1,
    'post_status'    => 'any',
);

$query = new WP_Query( $args );
if ( $query->have_posts() ) {
    while ( $query->have_posts() ) {
        $query->the_post();
        wp_delete_post( get_the_ID(), true ); // true = force delete, skip trash
        $deleted_count++;
    }
    wp_reset_postdata();
}

?>
<!DOCTYPE html>
<html>
<head>
    <title>Icons Slider - Cleanup Complete</title>
    <style>
        body { font-family: Arial, sans-serif; padding: 40px; background: #f1f1f1; }
        .container { max-width: 500px; margin: 0 auto; background: white; padding: 30px; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,.1); }
        h1 { color: #28a745; }
        p { color: #666; line-height: 1.6; }
        .success { background: #d4edda; padding: 15px; border-radius: 4px; margin: 20px 0; border-left: 4px solid #28a745; color: #155724; }
        .button { display: inline-block; padding: 10px 20px; margin-top: 20px; border-radius: 4px; text-decoration: none; font-weight: bold; background: #007bff; color: white; }
        .button:hover { background: #0056b3; }
    </style>
</head>
<body>
    <div class="container">
        <h1>✓ Cleanup Complete</h1>
        
        <div class="success">
            <strong><?php echo $deleted_count; ?> icon slide(s) have been permanently deleted.</strong>
        </div>
        
        <p>Your Icons Slider is now empty. You can now safely copy your website to a new location or start adding fresh slides.</p>
        
        <a href="<?php echo admin_url( 'edit.php?post_type=icon_slide' ); ?>" class="button">View Icon Slides</a>
    </div>
</body>
</html>
