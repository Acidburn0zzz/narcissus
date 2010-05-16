<?
/* Narcissus - Online image builder for the angstrom distribution
 * Koen Kooi (c) 2008-2010 - all rights reserved 
 *
 */


$base_pkg_set = " task-base angstrom-version ";

if (isset($_POST["action"]) && $_POST["action"] != "") {
	$action = $_POST["action"];
} else {
	print "Invalid action: $action";
	exit;
}

if (isset($_POST["machine"])) {
	$machine = escapeshellcmd(basename($_POST["machine"]));
} else {
	print "Invalid machine";
	exit;
}

if (isset($_POST["name"]) && $_POST["name"] != "") {
	$name = escapeshellcmd(basename($_POST["name"]));
} else {
	$name = "unnamed";
}

if (isset($_POST["pkgs"]) && $_POST["pkgs"] != "") {
	$pkg = $_POST["pkgs"];
} else {
	$pkg = "task-boot";
}

if (isset($_POST["release"]) && $_POST["release"] != "") {
	$release = $_POST["release"];
} else {
	$release = "stable";
}

if (isset($_POST["imagetype"]) && $_POST["imagetype"] != "") {
	$imagetype = $_POST["imagetype"];
} else {
	$imagetype = "tbz2";
	$imagesuffix = "tar.bz2";
}

if (isset($_POST["manifest"]) && $_POST["manifest"] != "") {
	$manifest = $_POST["manifest"];
} else {
	$manifest = "no";
}

if (isset($_POST["sdk"]) && $_POST["sdk"] != "") {
	$sdk = $_POST["sdk"];
} else {
	$sdk = "no";
}

if (isset($_POST["sdkarch"]) && $_POST["sdkarch"] != "") {
	$sdkarch = $_POST["sdkarch"];
} else {
	$sdkarch = "no";
}

switch($imagetype) {
	case "tbz2":
		$imagesuffix = "tar.bz2";
		break;
	case "ubifs":
		$imagesuffix = "ubi";
		break;
	default:
		$imagesuffix = $imagetype;
}

switch($action) {
	case "assemble_image":
		print "assembling\n";
		assemble_image($machine, $name, $imagetype, $manifest, $sdk, $sdkarch);
		break;
	case "configure_image":
		print "configuring\n";
		configure_image($machine, $name, $release);
		break;
	case "show_image_link":
		show_image_link($machine, $name, $imagesuffix, $manifest);
		break;
	case "install_package":
		print "installing $pkg\n";
		install_package($machine, $name, $pkg);
		break;
}


function show_image_link($machine, $name, $imagesuffix, $manifest) {
	$foundimage = 0;
	$foundsdimage = 0;
	$printedcacheinfo = 0;
	$printstring = "";
	
	$randomname = substr(md5(time()), 0, 6);
	$deploydir = "deploy/$machine/$randomname";
	mkdir($deploydir);
	
	$imagefiles = scandir("deploy/$machine");
	foreach($imagefiles as $value) {
		$location = "deploy/$machine/$value";
		// The !== operator must be used.  Using != would not work as expected
		// because the position of 'a' is 0. The statement (0 != false) evaluates 
		// to false.
		if(strpos($value, "$name-image-$machine-sd") !== false) {
			rename($location, "$deploydir/$value");
			$imgsize = round(filesize("$deploydir/$value") / (1024 * 1024),2);
			$printstring .= "<a href='$deploydir/$value'>$value</a> [$imgsize MiB]<br/> "; 
			$foundsdimage = 1;
			continue;
		}
		if(strpos($value, "$name-image-$machine.$imagesuffix") !== false) {
			rename($location, "$deploydir/$value");
			$imgsize = round(filesize("$deploydir/$value") / (1024 * 1024),2);
			$imagestring = "<br/><br/><a href='$deploydir/$value'>$value</a> [$imgsize MiB]: This is the rootfs '$name' for $machine you just built. This will get automatically deleted after 3 days.<br/>";
			if($manifest == "yes") {
				$imagestring .= "You can also have a look at the software <a href='deploy/$machine/$name-image-manifest.html' target='manifest'>manifest</a> for this rootfs<br/>";
			}
			$foundimage = 1;
		}
	}	
	
	if ($foundimage == 0) {
		print "Image not found, something went wrong :/";
	} else {
		print("$imagestring");
	}
	
	if($foundsdimage == 1) {
		print(" <br/><br/> The raw SD card image(s) below have a vfat partition populated with the bootloader and kernel, but an <b>empty</b> ext3 partition. You can extract the tarball to that partition to make it ready to boot.<br>The intended size for the SD card is encoded in the file name, e.g. 1GiB for a one gigabyte card.<br/><br/> $printstring");
	}
	
}

function configure_image($machine, $name, $release) {
	print "Machine: $machine, name: $name\n";
	passthru ("scripts/configure-image.sh $machine $name-image $release && exit");
}

function install_package($machine, $name, $pkg) {
	print "Machine: $machine, name: $name, pkg: $pkg\n";
	passthru ("scripts/install-package.sh $machine $name-image $pkg && exit", $installretval);
	print "<div id=\"retval\">$installretval</div>";
}

function assemble_image($machine, $name, $imagetype, $manifest, $sdk, $sdkarch) {
	print "Machine: $machine, name: $name, type: $imagetype\n";
	passthru ("fakeroot scripts/assemble-image.sh $machine $name-image $imagetype $manifest $sdk $sdkarch && exit", $installretval);
	print "<div id=\"retval-image\">$installretval</div>";
}



?>
