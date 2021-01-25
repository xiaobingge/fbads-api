<!DOCTYPE html>
<html>
<head lang="zh">
    <meta charset="UTF-8"/>
    <title>Tool</title>
    <meta http-equiv="X-UA-Compatible" content="IE=edge"/>
    <meta name="viewport" content="width=device-width, initial-scale=1.0, minimum-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta name="format-detection" content="telephone=no"/>
    <meta name="renderer" content="webkit" />
    <link href="https://cdn.bootcdn.net/ajax/libs/twitter-bootstrap/4.5.3/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.bootcdn.net/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
    <script src="https://cdn.bootcdn.net/ajax/libs/popper.js/1.16.0/umd/popper.min.js"></script>
    <script src="https://cdn.bootcdn.net/ajax/libs/twitter-bootstrap/4.5.3/js/bootstrap.min.js"></script>

</head>
<body>
<div class="container">
    <h2 style="text-align: center; display: block; padding: 20px 0">
        这是一个工具
    </h2>
</div>
<main role="main" class="container">
    <form>
        <div class="form-group">
            <label for="type">类型</label>
            <select class="form-control" id="type" name="type">
                <option value="url">url</option>
                <option value="handle">handle</option>
                <option value="title">title</option>
                <option value="productId">productId</option>
            </select>
        </div>
        <div class="form-group">
            <label for="shop_key">站点</label>
            <select class="form-control" id="shop_key" name="shop_key">
                <option value="-1">请选择</option>
                @foreach ($shop_keys as $shop)
                    <option value="{{  $shop  }}">{{  $shop  }}</option>
                @endforeach
            </select>
        </div>

        <div class="form-group">
            <label for="text">值</label>
            <input type="text" autocomplete="off" class="form-control" id="text" name="text">
        </div>

        <div class="form-group">
            <label for="async">异步</label>
            <div class="form-check form-check-inline">
                <input class="form-check-input" type="checkbox" id="async" name="async" value="1">
                <label for="async">shopify站点建议使用异步</label>
            </div>
        </div>

        <div class="form-group">
            <button type="button" name="submit" disabled="disabled" id="submit_btn" class="btn btn-primary">提交</button>
            <a href="/shopify/tool" class="btn btn-danger">刷新</a>
            <span class="red">完事刷新一下页面</span>
        </div>
    </form>

    <div id="errors"></div>


</main>
<script>
    !function ($) {
        $('#text').bind('change focusout mouseout', function () {
            $("#errors").html('');
            var text = $(this).val();
            if (text == '') {
                return false;
            }
            $('#submit_btn').attr('disabled', true);
            var type = $('#type').val(), shop_key = $('#shop_key').val();
            if (type == 'handle' || type == 'productId' || type == 'title') {
                if (shop_key != -1) {
                    $('#submit_btn').removeAttr('disabled');
                } else {
                    alert('请选择站点');
                    return false;
                }
            } else if (type == 'url' && shop_key != -1) {
                $('#submit_btn').removeAttr('disabled');
            } else {
                try {
                    var url = new URL(text);
                    var host = url.host;
                    $('#shop_key option[value="' + host + '"]').attr("selected", true);
                    // var pathname = url.pathname;
                    // var paths = pathname.split('/');
                    // var productId = paths[paths.length - 1];
                    // console.log(productId);
                    if ($('#shop_key option:selected').val() == '-1') {
                        $("#errors").html('<div class="alert alert-danger" role="alert">没有找到站点，请选择站点或者检查url</div>');
                        return false;
                    }

                    $('#submit_btn').removeAttr('disabled');
                } catch (e) {
                    $("#errors").html('<div class="alert alert-danger" role="alert">这不是一个合法的url</div>');
                }
            }
        });

        $('#submit_btn').click(function () {
            var disable = $(this).attr('disabled');
            if (disable) {
                return false;
            }
            var type = $('#type').val(), shop_key = $('#shop_key').val();
            var text = $('#text').val(), async = $('#async:checked').val();

            $.ajax({
                url: "/shopify/tool_ajax",
                type: "POST",
                data: {type, shop_key, text, async},
                dataType: "json",
                success: function (data) {
                    var errHtml = '';
                    if(Array.isArray(data.msg)) {
                        for (var i = 0; i < data.msg.length; i++) {
                            errHtml += '<div class="alert alert-warning" role="alert">' + data.msg[i] + '</div>';
                        }
                    } else {
                        errHtml = '<div class="alert alert-warning" role="alert">' + data.msg + '</div>';
                    }
                    $("#errors").html(errHtml);
                }
            })
        });
    }(jQuery);
</script>
</body>
</html>
