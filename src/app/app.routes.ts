
import {HTTP_INTERCEPTORS} from "@angular/common/http";
import {RouterModule, Routes} from "@angular/router";


// import components
//import {SplashComponent} from "./splash/splash.component";
import {HomeComponent} from "./home/home.component";

//import services



//import interceptors
import {DeepDiveInterceptor} from "./shared/interceptors/deep.dive.interceptor";
import {APP_BASE_HREF} from "@angular/common";
import {CookieService} from "ng2-cookies";
import {JoinService} from "./shared/services/join.service";





//create array of components
export const allAppComponents = [
	HomeComponent,
];

//setup routes
export const routes: Routes = [
	{path: "", component: HomeComponent},
	//{path: "", component: SplashComponent}
];




//array of providers
export const providers: any[] = [
	{provide: APP_BASE_HREF, useValue: window["_base_href"]},
	{provide: HTTP_INTERCEPTORS, useClass: DeepDiveInterceptor, multi: true},
	CookieService,
	JoinService
];

export const appRoutingProviders: any[] = [providers];

export const routing = RouterModule.forRoot(routes);