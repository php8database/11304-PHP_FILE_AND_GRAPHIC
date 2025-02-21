<style>
table {
    border-collapse: collapse;
    border-spacing: 0;
    border: 1px solid #ccc;
    margin: 10px 0;
}

table th,
table td {
    border: 1px solid #ccc;
    padding: 5px;
}

.header {
    text-align: center;
}
</style>

<?php
if(!empty($_FILES['file'])){
    move_uploaded_file($_FILES['file']['tmp_name'], "./files/{$_FILES['file']['name']}");
    echo $_FILES['file']['name']."上傳成功";
    $tableName = getfile("./files/{$_FILES['file']['name']}");
    displayData($tableName); // 顯示匯入資料
}

function getfile($path){
    try {
        $conn = new PDO("mysql:host=localhost", 'root', '');
        $conn->exec("CREATE DATABASE IF NOT EXISTS import");
        $conn->exec("USE import");
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $tableName = 'table_' . mt_rand(100000, 999999); // 生成隨機的表格名稱
        $file = fopen($path, 'r');

        $header_cols = fgetcsv($file); // 讀取CSV檔案的標題列

        // 處理BOM問題以及直接將編碼轉成 UTF-8
        $header_cols[0] = preg_replace('/^\xEF\xBB\xBF/', '', $header_cols[0]);

        // 清理欄位名稱
        $header_cols = array_map(function($col) {
            $clean_col = preg_replace("/[^a-zA-Z0-9_]/u", "", trim($col));
            return empty($clean_col) ? 'col'.mt_rand(1000,9999) : $clean_col; // 避免空欄位名稱
        }, $header_cols);

        // 建立資料表欄位
        $tmpcols = [];
        foreach ($header_cols as $hc) {
            $tmpcols[] = "`$hc` TEXT NOT NULL";
        }

        // 建立資料表
        $sql = "CREATE TABLE `$tableName` (
            id INT AUTO_INCREMENT PRIMARY KEY,";
        $sql .= join(",", $tmpcols);
        $sql .= ")";
        $conn->exec($sql);

        // 準備插入資料的SQL語句
        $stmt = $conn->prepare("INSERT INTO `$tableName` (`".join("`,`",$header_cols)."`) values(".str_repeat("?,",count($header_cols)-1)."?)");
        
        $count = 0;
        // 讀取每一列並插入資料
        while (($cols = fgetcsv($file)) !== false) {
            $cols = array_slice($cols, 0, count($header_cols)); // 確保每列資料數量符合欄位數量
            $stmt->execute($cols);
            $count++;
        }
        fclose($file);
        echo "資料匯入 $tableName 完成，共匯入 $count 筆資料<br>";
        return $tableName; // 返回資料表名稱
    } catch(PDOException $e) {
        echo "錯誤: " . $e->getMessage();
    }
}

// 顯示匯入資料
function displayData($tableName) {
    try {
        $conn = new PDO("mysql:host=localhost;dbname=import", 'root', '');
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // 查詢資料表中的所有資料
        $sql = "SELECT * FROM `$tableName`";
        $stmt = $conn->query($sql);

        // 顯示表格標題
        $columns = array_keys($stmt->fetch(PDO::FETCH_ASSOC)); // 取得欄位名稱
        echo "<h2>匯入的資料</h2>";
        echo "<table>";
        echo "<tr class='header'>";
        foreach ($columns as $column) {
            echo "<th>$column</th>";
        }
        echo "</tr>";

        // 顯示資料內容
        $stmt->execute();
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            echo "<tr>";
            foreach ($row as $data) {
                echo "<td>$data</td>";
            }
            echo "</tr>";
        }
        echo "</table>";
    } catch (PDOException $e) {
        echo "顯示資料錯誤: " . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="zh-TW">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>文字檔案匯入</title>
    <link rel="stylesheet" href="style.css">
</head>

<body>
    <h1 class="header">文字檔案匯入練習</h1>
    <!---建立檔案上傳機制--->
    <form action="?" method="post" enctype="multipart/form-data">
        <label for="file">文字檔:</label><input type="file" name="file" id="file">
        <input type="submit" value="上傳">
    </form>
</body>

</html>