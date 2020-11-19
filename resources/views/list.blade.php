
<div class="container">
    <div class="row justify-content-center">
        <a href="{{ route('facebook_login') }}" target="_blank">添加新的</a>
        <table class="layui-table">
            <thead>
            <tr>
                <th>ActID</th>
                <th>userId</th>
                <th>Name</th>
                <th>Email</th>
                <th>操作</th>
            </tr>
            </thead>
            <tbody>
            @foreach ($result as $info)
                <tr>
                    <td>{{ $info->ad_account }}</td>
                    <td>{{ $info->user_id }}</td>
                    <td>{{ $info->auth->name }}</td>
                    <td>{{ $info->auth->email }}</td>
                    <td><a href="{{  url('/facebook/campaigns?account=' . $info->ad_account_int)  }}"
                           target="_blank">广告系列</a>
                        <a href="{{  url('/facebook/adsets?account=' . $info->ad_account_int)  }}"
                           target="_blank">广告组</a>
                        <a href="{{  url('/facebook/ads?account=' . $info->ad_account_int)  }}"
                           target="_blank">广告</a>
                        <a href="{{  url('/facebook/adspixels?account=' . $info->ad_account_int)  }}"
                           target="_blank">像素</a>

                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>
        <div id="pagination"></div>
    </div>
</div>
