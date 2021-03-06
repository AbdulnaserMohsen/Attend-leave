
@extends(Route::current()->getName() == 'year_month_statistics' ? 'layouts.admin_layout' : 'layouts.all')

@section(Route::current()->getName() == 'year_month_statistics' ? 'admin_css' : 'headerCss' )
	
	<link rel="stylesheet" type="text/css" href="{{ asset('css/admin.css')}}">

@endsection

@section(Route::current()->getName() == 'year_month_statistics' ? 'main_admin' : 'content')
				
				@if(Route::current()->getName() != 'year_month_statistics')
				<div class="container">
				@endif
                  <!--==========================
				      Services Section
				    ============================-->
				    <section id="services">
				      	<div class="">
					        <div class="section-header">
					          <h2>{{ __('admin.choose_year_month') }}</h2>
					        </div>

					        <div class="row justify-content-end">
					        	<div class="dataTables_length " >
				        			<label>{{ __('admin.years') }}
				        				<select id="mytable_length" name="mytable_length" aria-controls="mytable" class="classic" data-url='{{ route("year_month_statistics", ":year") }}'>
				        					@foreach($years as $year_attend => $year_counter)	
				        						<option @if($year_attend == $year) selected disabled @endif value="{{$year_attend}}">{{$year_attend}}</option>
				        					@endforeach
				        				</select>
				        			</label>
				        		</div>
				        	</div>

					        <div class="row">
					          @foreach($months as $month => $month_counter)	
					          <div class="col-lg-6">
					            <div class="box wow fadeInLeft">
					              <div class="icon"><i class="fa fa-times-rectangle-o"></i></div>
					              <h4 class="title counter">
					              	<span> <span class="js-number-counter">{{$month_counter}}</span> {{ __('admin.days') }}</span>
					              </h4>
					              @if(Route::current()->getName() == 'year_month_statistics')
					              	<h4 class="title"><a href="{{route('statictics',['year'=>$year,'month'=>\Carbon\Carbon::parseFromLocale($month)->month,'worker_paginate'=> Cache::get('worker_paginate') ,'table_paginate'=> Cache::get('table_paginate') ] )}}">{{$month}}</a></h4>
					              @else
					              	<h4 class="title"><a href="{{route('user_statictics',['year'=>$year,'month'=>\Carbon\Carbon::parseFromLocale($month)->month,'table_paginate'=> Cache::get('table_paginate') ] )}}">{{$month}}</a></h4>
					              @endif
					              <p class="description"></p>
					              
					            </div>
					          </div>
					          @endforeach

					        </div>
				      	</div>
				    </section>
				    <!-- #services -->
				@if(Route::current()->getName() != 'year_month_statistics')
				</div>
				@endif

		
		
@endsection

@section(Route::current()->getName() == 'year_month_statistics' ? 'admin_js' : 'footerJs')

<script >
	
	$(document).on( "change","#mytable_length", function() 
	{
		var year = $(this).val();
		// //console.log(length);
		var url = $(this).data('url');
		 url = url.replace(':year', year);
		// //console.log(url);
		window.location.href = url;
		// //window.location.replace(url);

	});

</script>
	
@endsection

