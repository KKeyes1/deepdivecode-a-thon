import {NgModule} from "@angular/core";
import {HttpClientModule} from "@angular/common/http";
import {BrowserModule} from "@angular/platform-browser";
import {ReactiveFormsModule} from "@angular/forms";
import {AppComponent} from "./app.component";
import {allAppComponents, appRoutingProviders, routing} from "./app.routes";
import {JwtModule} from "@auth0/angular-jwt";

const moduleDeclarations = [AppComponent];

const JwtHelper = JwtModule.forRoot({
	config: {
		tokenGetter: () => {
			return localStorage.getItem("jwt-token");
		},
		skipWhenExpired: true,
		whitelistedDomains: ["localhost:7272", "https:whoisken.com/"],
		headerName: "X-JWT-TOKEN",
		authScheme: ""
	}
});

@NgModule({
	imports:      [BrowserModule, HttpClientModule, ReactiveFormsModule, routing, JwtHelper],
	declarations: [...moduleDeclarations, ...allAppComponents],
	bootstrap:    [AppComponent],
	providers:    [...appRoutingProviders]
})
export class AppModule {}