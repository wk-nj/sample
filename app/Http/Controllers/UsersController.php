<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Requests;
use App\Models\User;
use Mail;
class UsersController extends Controller
{
	public function __construct()
	{
		$this->middleware('auth',[
			'except'=>['create','show','store','index','confirmEmail']
		]);

		$this->middleware('guest',[
			'only'=>['create']
		]);
	}
	//用户列表
	public function index()
	{
		$users = User::paginate(10);
		return view('users.index', compact('users'));
	}

	//用户注册页面跳转
    public function create()
    {
    	return view('users.create');
    }

    //展示用户信息与发布的微博
    public function show(User $user){
    	$statuses = $user->statuses()->orderBy('created_at','desc')->paginate(30);
    	return view('users.show', compact('user','statuses'));
    }

    //用户注册发送激活邮件
     public function store(Request $request)
    {
        $this->validate($request, [
            'name' => 'required|max:50',
            'email' => 'required|email|unique:users|max:255',
            'password' => 'required|confirmed|min:6'
        ]);

        $user = User::create([
        	'name'=>$request->name,
        	'email'=>$request->email,
        	'password'=>bcrypt($request->password)
        ]);
        $this->sendEmailConfirmationTo($user);
        session()->flash('success', '验证邮件已发送到你的注册邮箱上，请注意查收。');
        return redirect('/');
    }

    //发送邮件
    public function sendEmailConfirmationTo($user)
    {
    	$view = 'emails.confirm';
        $data = compact('user');
        $to = $user->email;
        $subject = "感谢注册 Sample 应用！请确认你的邮箱。";

        Mail::send($view, $data, function ($message) use ($to, $subject) {
            $message->to($to)->subject($subject);
        });
    }

    //邮箱验证与用户激活
    public function confirmEmail($token)
    {
        $user = User::where('activation_token', $token)->firstOrFail();

        $user->activated = true;
        $user->activation_token = null;
        $user->save();
        \Auth::login($user);
        session()->flash('success', '恭喜你，激活成功！');
        return redirect()->route('users.show', [$user]);
    }

    //用户编辑页面跳转
    public function edit(User $user)
    {
    	$this->authorize('update', $user);
    	return view('users.edit', compact('user'));
    }

    //更新用户信息
    public function update(User $user, Request $request)
    {
    	$this->validate($request, [
    		'name'=>'required|max:50',
    		'password'=>'nullable|confirmed|min:6'
    	]);

    	$this->authorize('update', $user);

    	$data = [];

    	$data['name']= $request->name;

    	if($password = $request->password){
    		$data['password'] = bcrypt($password);
    	}

    	$user->update($data);

    	session()->flash('success', '更新成功');

    	return redirect()->route('users.show', $user);
    }

    //删除用户
    public function destroy(User $user)
    {
    	$this->authorize('destroy',$user);
    	$user->delete();
    	session()->flash('success','删除成功！');
    	return back();
    }

    //用户关注列表
    public function followings(User $user)
    {
    	$users = $user->followings()->paginate(30);
    	$title = '关注列表';
    	return view('users.show_follow', compact('users','title'));
    }

    public function followers(User $user)
    {
        $users = $user->followers()->paginate(30);
        $title = '粉丝列表';
        return view('users.show_follow', compact('users', 'title'));
    }
}
