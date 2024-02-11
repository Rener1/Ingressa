<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Curso;
use App\Models\Cota;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Laravel\Jetstream\Jetstream;
use App\Actions\Fortify\PasswordValidationRules;
use App\Http\Requests\UserRequest;
use App\Models\TipoAnalista;

class UserController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $this->authorize('isAdmin', User::class);
        $users = User::where('role', User::ROLE_ENUM['analista'])->paginate(15);
        $tipos = TipoAnalista::all();
        $cursos = Curso::distinct()->orderBy('nome')->pluck('cod_curso', 'nome');
        $cotas = Cota::all();
        return view('user.index', compact('users', 'tipos', 'cursos', 'cotas'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $this->authorize('isAdmin', User::class);
        return view('user.create');
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(UserRequest $request)
    {
        $this->authorize('isAdmin', User::class);
        $request->validated();
        $user = new User();
        $user->setAtributes($request);
        $user->role = User::ROLE_ENUM['analista'];
        $user->email_verified_at = now();
        $user->primeiro_acesso = false;
        $user->save();
        foreach ($request->tipos_analista as $tipo_id) {
            $user->tipo_analista()->attach(TipoAnalista::find($tipo_id));
        }
        foreach ($request->cursos_analista as $cod_curso) {
            $user->analistaCursos()->attach(Curso::where('cod_curso', $cod_curso)->get());
        }
        if (!$user->tipo_analista()->whereIn('tipo', [TipoAnalista::TIPO_ENUM['heteroidentificacao'], TipoAnalista::TIPO_ENUM['medico']])->exists()) {
            $user->analistaCotas()->attach(Cota::find($request->cotas_analista));
        }

        return redirect(route('usuarios.index'))->with(['success' => 'Analista cadastrado com sucesso!']);
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\User  $user
     * @return \Illuminate\Http\Response
     */
    public function show(User $user)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\User  $user
     * @return \Illuminate\Http\Response
     */
    public function edit(User $user)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\User  $user
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password'         => 'required',
            'password_confirmation' => 'required|same:password',
        ]);

        if ($validator->fails()) {
            return redirect('editar')->withErrors($validator->errors())->withInput();
        }

        $user = User::find($request->id);
        $user->setAtributes($request);
        $user->primeiro_acesso = false;
        $user->update();

        return redirect(route('logar'))->with(['success' => 'Primeiro acesso realizado! Digite o e-mail e senha para entrar agora.']);
    }

    public function updateAnalista(Request $request)
    {
        $user = User::find($request->user_id);
        $validator = Validator::make($request->all(), [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email,' . $user->id],
            'tipos_analista_edit' => 'required',
            'cotas_analista_edit' => 'exists:cotas,id',
            'cursos_analista_edit' => 'required|exists:cursos,cod_curso',
        ]);

        $validator->sometimes('cotas_analista_edit', 'required', function ($input) {
            return in_array(TipoAnalista::TIPO_ENUM['geral'], $input->tipos_analista_edit);
        });

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator->errors())->withInput();
        }

        $user->tipo_analista()->sync($request->tipos_analista_edit);

        $cursos = Curso::whereIn('cod_curso', $request->cursos_analista_edit)->pluck('id');

        $user->analistaCursos()->sync($cursos);

        if (!$user->tipo_analista()->whereIn('tipo', [TipoAnalista::TIPO_ENUM['heteroidentificacao'], TipoAnalista::TIPO_ENUM['medico']])->exists()) {
            $user->analistaCotas()->sync($request->cotas_analista_edit);
        }

        $user->name = $request->name;
        $user->email = $request->email;
        $user->update();

        return redirect()->back()->with(['success' => 'Analista editado com sucesso']);
    }

    public function infoUser(Request $request)
    {
        $user = User::find($request->user_id);

        $userInfo = [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'cargos' => $user->tipo_analista,
            'cursos' => $user->analistaCursos,
            'cotas' => $user->analistaCotas,
        ];

        return response()->json($userInfo);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\User  $user
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $this->authorize('isAdmin', User::class);
        $user = User::find($id);
        foreach ($user->tipo_analista()->get() as $tipo) {
            $tipo->pivot->delete();
        }
        $user->delete();

        return redirect(route('usuarios.index'))->with(['success' => 'Analista deletado com sucesso!']);
    }
}
