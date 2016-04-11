@extends('layouts.interno')

@section('titulo')
    <h2>Cadastrar Usuário</h2>
@stop

@section('content')
    <div class="panel panel-default ">
        <div class="panel-body">
            <div class="ibox float-e-margins wrapper wrapper-content">
                <div class="ibox-title">
                    <h5>Formulário de Cadastro de Usuário</h5>
                </div>
                <div class="ibox-content">
                    {!! Form::open(["url" => "/security/usuarios/create", "method" => "POST", "id" => "form", "role" => "form"]) !!}
                        @include('security.usuarios.includes.form')
                    {!! Form::close() !!}
                </div>
            </div>
        </div>
    </div>
@stop

@section('scripts')
    @parent
    <script src="{{ asset('/assets/vendor/jasny/jasny-bootstrap.min.js') }}"></script>
@stop