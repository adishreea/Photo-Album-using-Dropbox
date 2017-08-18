<?php

    // display all errors on the browser
    error_reporting(E_ALL);
    ini_set('display_errors','On');

    require_once("DropboxClient.php");
    $dropbox = new DropboxClient(
        array('app_key' => "wgjq19fmy2ipjwr", 
            'app_secret' => "xmmnfhazqrwd8gi", 
            'app_full_access' => false,), 'en');
    
    // first try to load existing access token
    $access_token = load_token("access");
    if(!empty($access_token)) {
        $dropbox->SetAccessToken($access_token);
    }
    elseif(!empty($_GET['auth_callback'])) 
    // are we coming from dropbox's auth page?
    {
        // then load our previously created request token
        $request_token = load_token($_GET['oauth_token']);
        if(empty($request_token)) 
            die('Request token not found!');
        // get & store access token, the request token is not needed anymore
        $access_token = $dropbox->GetAccessToken($request_token);	
        store_token($access_token, "access");
        delete_token($_GET['oauth_token']);   
    }
    // checks if access token is required
    if(!$dropbox->IsAuthorized())
    {
        // redirect user to dropbox auth page
        $return_url = "http://".$_SERVER['HTTP_HOST'].$_SERVER['SCRIPT_NAME']."?auth_callback=1";
        $auth_url = $dropbox->BuildAuthorizeUrl($return_url);
        $request_token = $dropbox->GetRequestToken();
        store_token($request_token, $request_token['t']);
        die("Authentication required. <a href='$auth_url'>Click here.</a>"); 
    }
    function store_token($token, $name)
        { 
            if(!file_put_contents("tokens/$name.token", serialize($token)))
                die('<br />Could not store token! <b>Make sure that the directory `tokens` exists and is writable!</b>');
    } 
    function load_token($name)
    {
        if(!file_exists("tokens/$name.token")) 
            return null;
        return @unserialize(@file_get_contents("tokens/$name.token"));
    } 
    function delete_token($name)
    { 
        @unlink("tokens/$name.token"); 
    }
    function enable_implicit_flush()
    {
        @apache_setenv('no-gzip', 1);
        @ini_set('zlib.output_compression', 0);
        @ini_set('implicit_flush', 1);
        for ($i = 0; $i < ob_get_level(); $i++){
            ob_end_flush();
        }
        ob_implicit_flush(1);
        echo "<!-- ".str_repeat(' ', 2000)." -->";
    } 
?>
<html>
    <head>
        <style>
            table{ 
                border-collapse: collapse;
            }  
            .data tr, .data td,.data th{
                border: 1px solid black;
            }
        </style>
    </head>
    <body>
        <div style="margin-left: 50px; border:1px solid black" >
            <form action="<?= $_SERVER['PHP_SELF']?>" method="post" enctype="multipart/form-data">
                <table align="center">
                <tr><td>Image Upload</td><td><input type="file" name="fileToUpload"/></td></tr>
                <br/>
                <tr><td></td><td><input type="submit" value="Upload" name="upload"/></td></tr>
                </table>
            </form>
        </div>
        <div> 
            <?php
                $fileList = $dropbox->GetFiles("",false);
                //echo var_dump($fileList);
                //echo $fileList["path"];   
            ?>
            <table class="data" align="center">
                <thead>
                    <th style="width: 150px;">Name</th>
                    <th  style="width: 150px;">Show</th>
                    <th  style="width: 40px;">Delete</th>
                </thead>
                <tbody> 
                    <?php 
                        $file="";	    
                        $i=0;
                        foreach($fileList as $key=>$value){
                    ?>
                    <tr>
                        <td align="center"><?php echo $key;?></td>
                        <td align="center">
                            <a href="album.php?show=<?php echo $key;?>">show</a>
                        </td>
                        <td align="center">
                            <a href="album.php?delete=<?php echo $key;?>">
                                <img src="DeleteRed.png"  style="height:20px; width:20px;"/>
                            </a>
                        </td>
                    </tr>
                    <?php   
                        }   
                    ?>
                </tbody>
            </table>
        </div>
        <div id="simg" style=" margin-left: auto; margin-right: auto; margin-top: 20px; height: 500px;width:800px">
            <?php 
                if(isset($_GET['show'])){
                    echo "<img style=\" display: block; margin: auto;\" src='".$dropbox->GetLink($_GET['show'],false)."'/></br>"; 
                }
                else{
                    $jpg_files = $dropbox->Search("/", ".jpg", 1);
                    $jp =  reset($jpg_files); 
                    echo "<img style=\" display: block; margin: auto;\" src='".$dropbox->GetLink($jp->path,false)."'/></br>"; 
                } 
            ?>
        </div>
    </body>
</html>
<?php
    /*onclick="showimg('<?php echo $key;?>')"*/
    if(isset($_FILES["fileToUpload"])){
        $target_dir = "C:/xampp/htdocs/project7/";
        $target_file = $target_dir . basename($_FILES["fileToUpload"]["name"]);
        if (move_uploaded_file($_FILES["fileToUpload"]["tmp_name"], $target_file)) {
            $dropbox->UploadFile($target_file);
            unlink($target_file);
            header("location: album.php");
        } 
    } 
    if(isset($_GET["delete"])){
        $dropbox->Delete($_GET["delete"]);
        header("location: album.php");     
    }
?>