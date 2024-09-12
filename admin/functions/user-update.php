<?php

// Ana fonskiyon dosyası ekleniyor.
include("main-function.php");

// -----> Kullanıcı bilgileri güncelleniyor
if (isset($_POST["user_info_update"])) {

    if (isset($_FILES['resim']) && !empty($_FILES['resim']['name'])) { // Resim yüklenmişse bu kısım çalışır.

        // Veritabanına ve Klasöre Resim Ekleniyor.
        klasoryap(); // Resmin yüklenmesi için bir arşiv yolu oluşturur.

        $hata = $_FILES['resim']['error']; // Hata kodu bir değişkenine aktarıldı.
        if ($hata != 0) {
            echo "Resim gödnerilirken bir hata oluştu.";
            echo "<br>";
            echo $hata;
            exit();
        }

        $resimBoyutu = $_FILES['resim']['size']; // Resmin boyutu bir değişkene aktarıldı.
        if ($resimBoyutu > (1024 * 1024 * 2)) {
            echo "Resim boyutu 2 MB den büyük olamaz.";
            exit();
            /*
		Yukarıda ki işlem byte, kilobayt ve megabayt ın çarpımıdır.
		2 Mb lik bir limit belirtilmiştir.
	    */
        }

        $tip = $_FILES['resim']['type']; // Resim tipi yanı uzantısı bir değişkene aktarıldı.
        if ($tip != 'image/jpeg' && $tip != 'image/png') {
            echo "Sadece JPEG ve PNG dosya türleri destekleniyor.";
            exit();
        }

        $host_adi = $_SERVER["HTTP_HOST"]; // Host adı buraya ekleniyor.
        $resimAdi = $_FILES['resim']['name']; // Resim adını bir değişkene aktardık.
        $uzantisi = explode('.', $resimAdi); // Resmin uzantısını almak için patalttık.
        $uzantisi = $uzantisi[count($uzantisi) - 1]; // Resmin uzantısını bir değişkene aktardık.
        $parcalama = pathinfo($resimAdi, PATHINFO_EXTENSION); // Resmin adını bir değişkene aktardık.
        $SadeceResimAdi = substr(str_replace("." . $parcalama, "", $resimAdi), 0, 50); // Resim adını almak için patlattık.
        $ResimSef = permalink($SadeceResimAdi); // Resmin adını aldık.
        $yuklame_klasoru = "../../images/"; // Resimlerin yükleneceği dizini bir değişkene aktardık.
        $yukleme_yolu = date("Y") . "/" . date("m") . "/"; // Resmin Yükleneceği arşiv yolunu bir değişkene aktardık. (Arşiv klasoryap ile oluşturulmuştu.)
        $klasor = $yuklame_klasoru . $yukleme_yolu; // Resmin tam yükleneceği adresi bir değişkene aktardık.
        $yeni_adi = $klasor . time() . "-" . $ResimSef . "." . $uzantisi; // HTML etiketi için bir bölüm ayarladık.
        $vt_yolu = "https://" . $host_adi . "/images/" . $yukleme_yolu . time() . "-" . $ResimSef . "." . $uzantisi; //Yükleme yolunu bir klasöre atatık.
        $vt_adi = time() . "-" . $ResimSef . "." . $uzantisi; //Veritabanı için benzersiz bir isim oluşturduk.

        $uploaded_image = $_FILES['resim']['tmp_name']; // Resim adı bir değişkene aktarılıyor
        list($width, $height) = getimagesize($uploaded_image); // Resmin en ve boyu öğreniliyor
        $image_user_ip = $_SERVER["REMOTE_ADDR"]; // Üye ip adresi
        $image_user_agent = $_SERVER['HTTP_USER_AGENT']; // Üye tarayıcı bilgisi


        move_uploaded_file($_FILES["resim"]["tmp_name"], $yeni_adi); // İlgili adrese taşındı.

        $images = $db->prepare("INSERT into images set
        image_name=:image_name,
        image_title=:image_title,
        image_link=:image_link,
        image_width=:image_width,
        image_height=:image_height,
        image_description=:image_description,
        image_type=:image_type,
        image_user_agent=:image_user_agent,
        image_user_ip=:image_user_ip
        ");

        $insert = $images->execute(array(
            'image_name' => $vt_adi,
            'image_title' => $SadeceResimAdi,
            'image_link' => $vt_yolu,
            'image_width' => $width,
            'image_height' => $height,
            'image_description' => $SadeceResimAdi,
            'image_type' => $uzantisi,
            'image_user_agent' => $image_user_agent,
            'image_user_ip' => $image_user_ip
        ));

        if ($insert) {
            // Başarılı ekleme işlemi
        } else {
            // Hata durumunda işlemler
            $errorInfo = $images->errorInfo();
            // Hata mesajlarını görüntüleme
            echo "Hata: " . $errorInfo[2];
            exit();
        }


        $son_resim_id = $db->lastInsertId(); // Son kaydedilen ID bir değişkene aktarıldı.

    } else { // Resim yüklenmemişse bu kısım çalışır.
        $son_resim_id = $_POST["image_id"];
    }

    // Resimler veritabanına kayı tedilyor.
    $user_id = $_POST["user_id"];
    $user_name = $_POST["user_name"];
    $user_mail = $_POST["user_mail"];
    $user_status = $_POST["user_status"];
    $user_role = $_POST["user_role"];
    $user_image = $son_resim_id;


    $users = $db->prepare("UPDATE users SET

    user_name=:user_name,
    user_mail=:user_mail,
    user_status=:user_status,
    user_role=:user_role,
    user_image=:user_image

    WHERE user_id=$user_id");

    $update = $users->execute(array(

        'user_name' => $user_name,
        'user_mail' => $user_mail,
        'user_status' => $user_status,
        'user_role' => $user_role,
        'user_image' => $user_image
    ));

    if ($update) {

        header('Location:../process.php?user=user-update&user_id='.$user_id.'&update=ok');
        exit;
    } else {

        header('Location:../process.php?user=user-update&user_id='.$user_id.'&update=no');
        exit;
    }
}

// -----> Kullanıcı şifresi güncelleniyor
if (isset($_POST["user_password_update"])) {

    $user_id = $_POST["user_id"];

    $users = $db->prepare("UPDATE users SET

    user_password=:user_password

    WHERE user_id=$user_id");

    $update = $users->execute(array(
        'user_password' => md5($_POST["user_password"])
    ));

    if ($update) {

        header("Location:../user.php?status=approved&password=ok");
        exit;
    } else {

        header("Location:../user.php?status=approved&password=no");
        exit;
    }
}
