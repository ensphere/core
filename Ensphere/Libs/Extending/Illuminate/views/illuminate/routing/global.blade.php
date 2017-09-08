@foreach( $parameters as $name => $value )
    {!! Form::hidden( $name, request( $name ) ) !!}
@endforeach
