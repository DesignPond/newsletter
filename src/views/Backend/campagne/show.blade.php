@extends('newsletter::Backend.layouts.master')
@section('content')

<style type="text/css">
    #StyleNewsletter h2, #StyleNewsletterCreate h2{
        color: {{ $campagne->newsletter->color }};
    }
    #StyleNewsletter .contentForm h3, #StyleNewsletter .contentForm h4,
    #StyleNewsletterCreate .contentForm h3, #StyleNewsletterCreate .contentForm h4
    {
        color: {{ $campagne->newsletter->color }};
    }
</style>

<div id="main" ng-app="newsletter" data-site="{{ $campagne->newsletter->site_id }}"><!-- main div for app-->

    <div class="row">
        <div class="col-md-12">

            <p><a href="{{ url('build/newsletter') }}" class="btn btn-info"><i class="fa fa-arrow-left"></i>  &nbsp;&nbsp;Retour aux newsletter</a></p>

            <input id="campagne_id" value="{{ $campagne->id }}" type="hidden">

            <div class="component-build"><!-- Start component-build -->
                <div id="optionsNewsletter">

                    <a href="{{ url('build/campagne/'.$campagne->id.'/edit') }}" class="btn btn-primary btn-block"><i class="fa fa-pencil"></i>  &nbsp;&Eacute;diter la campagne</a>
                    <a target="_blank" href="{{ url('campagne/'.$campagne->id) }}" class="btn btn-info btn-block"><i class="fa fa-eye"></i>  &nbsp;Aperçu de la campagne</a>
                    <a href="{{ url('build/content/'.$campagne->id) }}" class="btn btn-warning btn-block"><i class="fa fa-sort"></i>  &nbsp;Ordre</a>
                    <hr/>

                    <form action="{{ url('build/send/test') }}" enctype="multipart/form-data" method="POST" class="form">{!! csrf_field() !!}
                        <label><strong>Envoyer un test</strong></label>
                        <div class="input-group">
                            <input required name="email" value="" type="email" class="form-control">
                            <input name="id" value="{{ $campagne->id }}" type="hidden">
                            <span class="input-group-btn"><button class="btn btn-brown" type="submit">Go!</button></span>
                        </div><!-- /input-group -->
                    </form>

                </div>

                <div id="StyleNewsletter" class="onBuild">

                    <!-- Logos -->
                    @include('newsletter::Email.send.logos')
                    <!-- Header -->
                    @include('newsletter::Email.send.header')

                    @if(config('newsletter.pdf'))
                        @include('newsletter::Email.send.link')
                    @endif

                    <div id="viewBuild">
                        <div id="sortable">
                            @if(!$campagne->content->isEmpty())
                                @foreach($campagne->content as $bloc)
                                    @if(in_array($bloc->type->id ,array_keys(config('newsletter.components'))))
                                        <div class="bloc_rang" id="bloc_rang_{{ $bloc->id }}" data-rel="{{ $bloc->id }}">
                                            {!! view('newsletter::Backend.build.edit.'.$bloc->type->partial)->with(['bloc' => $bloc, 'campagne' => $campagne])->__toString() !!}
                                        </div>
                                    @endif
                                @endforeach
                            @endif
                        </div>
                    </div>

                </div>

                <div id="build"><!-- Start build -->

                    @if(!$blocs->isEmpty())
                        @foreach($blocs as $bloc)
                            @if(in_array($bloc->id ,array_keys(config('newsletter.components'))))
                                <div class="create_bloc" id="create_{{ $bloc->id }}">
                                    {!! view('newsletter::Backend.build.create.'.$bloc->template)->with(['bloc' => $bloc, 'campagne' => $campagne])->__toString() !!}
                                </div>
                            @endif
                        @endforeach
                    @endif

                    <div class="component-menu">
                        <h5>Composants</h5>
                        <a name="componant"></a>
                        <div class="component-bloc">
                            @if(!$blocs->isEmpty())
                                @foreach($blocs as $bloc)
                                    @if(in_array($bloc->id ,array_keys(config('newsletter.components'))))
                                        {!! view('newsletter::Backend.build.blocs')->with(array('bloc' => $bloc)) !!}
                                    @endif
                                @endforeach
                            @endif
                        </div>
                    </div><!-- End build -->

                </div>
            </div><!-- End component-build -->

        </div><!-- end 12 col -->
    </div><!-- end row -->

</div><!-- end main div for app-->

@stop