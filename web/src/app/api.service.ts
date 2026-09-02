import { Injectable, inject, signal } from '@angular/core';
import { HttpClient, HttpHeaders } from '@angular/common/http';
import { Observable } from 'rxjs';
import { tap } from 'rxjs/operators';
import { environment } from '../environments/environment';

export interface Resource {
  id: number;
  branch_id: number;
  name: string;
  capacity: number;
}

export interface Availability {
  resource_id: number;
  date: string;
  booked_slots: string[];
}

export interface Booking {
  id: number;
  resource_id: number;
  branch_id: number;
  customer_name: string;
  slot_start: string;
  slot_end: string;
  status: string;
}

export interface NewBooking {
  resource_id: number;
  branch_id: number;
  customer_name: string;
  slot_start: string;
  slot_end: string;
}

const TOKEN_KEY = 'mtb.token';

/**
 * Thin wrapper over the booking API. Holds the tenant token from /login and
 * attaches it to every later call.
 */
@Injectable({ providedIn: 'root' })
export class ApiService {
  private readonly http = inject(HttpClient);
  private readonly base = environment.apiUrl;
  readonly token = signal<string | null>(this.readToken());

  isAuthenticated(): boolean {
    return this.token() !== null;
  }

  login(email: string, password: string): Observable<{ token: string }> {
    return this.http
      .post<{ token: string }>(`${this.base}/login`, { email, password })
      .pipe(tap((res) => this.storeToken(res.token)));
  }

  logout(): void {
    this.storeToken(null);
  }

  resources(): Observable<{ data: Resource[] }> {
    return this.http.get<{ data: Resource[] }>(`${this.base}/resources`, { headers: this.authHeaders() });
  }

  availability(resourceId: number, date: string): Observable<Availability> {
    return this.http.get<Availability>(
      `${this.base}/resources/${resourceId}/availability?date=${date}`,
      { headers: this.authHeaders() },
    );
  }

  book(payload: NewBooking): Observable<{ data: Booking }> {
    return this.http.post<{ data: Booking }>(`${this.base}/bookings`, payload, {
      headers: this.authHeaders(),
    });
  }

  private authHeaders(): HttpHeaders {
    return new HttpHeaders({ Authorization: `Bearer ${this.token() ?? ''}` });
  }

  private storeToken(token: string | null): void {
    this.token.set(token);
    if (typeof localStorage === 'undefined') {
      return;
    }
    if (token) {
      localStorage.setItem(TOKEN_KEY, token);
    } else {
      localStorage.removeItem(TOKEN_KEY);
    }
  }

  private readToken(): string | null {
    return typeof localStorage === 'undefined' ? null : localStorage.getItem(TOKEN_KEY);
  }
}
