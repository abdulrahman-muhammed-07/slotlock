import { TestBed } from '@angular/core/testing';
import { provideHttpClient } from '@angular/common/http';
import { HttpTestingController, provideHttpClientTesting } from '@angular/common/http/testing';
import { ApiService } from './api.service';
import { environment } from '../environments/environment';

describe('ApiService', () => {
  let service: ApiService;
  let http: HttpTestingController;

  beforeEach(() => {
    localStorage.clear();
    TestBed.configureTestingModule({
      providers: [ApiService, provideHttpClient(), provideHttpClientTesting()],
    });
    service = TestBed.inject(ApiService);
    http = TestBed.inject(HttpTestingController);
  });

  afterEach(() => http.verify());

  it('posts credentials to /login and keeps the returned token', () => {
    service.login('owner@northwind.test', 'password').subscribe();

    const req = http.expectOne(`${environment.apiUrl}/login`);
    expect(req.request.method).toBe('POST');
    expect(req.request.body).toEqual({ email: 'owner@northwind.test', password: 'password' });
    req.flush({ token: 'abc123' });

    expect(service.token()).toBe('abc123');
    expect(service.isAuthenticated()).toBeTrue();
  });

  it('sends the bearer token on a booking request', () => {
    service.login('owner@northwind.test', 'password').subscribe();
    http.expectOne(`${environment.apiUrl}/login`).flush({ token: 'abc123' });

    service
      .book({
        resource_id: 1,
        branch_id: 1,
        customer_name: 'Sam',
        slot_start: '2026-01-01T19:00:00',
        slot_end: '2026-01-01T20:00:00',
      })
      .subscribe();

    const req = http.expectOne(`${environment.apiUrl}/bookings`);
    expect(req.request.headers.get('Authorization')).toBe('Bearer abc123');
    req.flush({ data: {} });
  });
});
