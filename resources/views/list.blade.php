
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
                    <td></td>
                </tr>
            @endforeach
            </tbody>
        </table>
        <div id="pagination"></div>
    </div>
</div>
