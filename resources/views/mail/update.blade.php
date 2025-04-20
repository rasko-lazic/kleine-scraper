New batch of games matching your wishlist

@foreach($notifications as $notification)
    <a href="{{ $notification['url'] }}" target="_blank">{{ $notification['title'] }}</a> <br /><br />
@endforeach
