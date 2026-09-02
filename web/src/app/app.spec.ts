import { TestBed } from '@angular/core/testing';
import { of, throwError } from 'rxjs';
import { HttpErrorResponse } from '@angular/common/http';
import { App } from './app';
import { ApiService } from './api.service';

function apiStub(overrides: Partial<ApiService> = {}): Partial<ApiService> {
  return {
    isAuthenticated: () => false,
    login: () => of({ token: 't' }),
    logout: () => undefined,
    resources: () => of({ data: [] }),
    availability: () => of({ resource_id: 1, date: '2026-01-01', booked_slots: [] }),
    book: () => of({ data: {} as never }),
    ...overrides,
  };
}

function setup(overrides: Partial<ApiService> = {}) {
  TestBed.configureTestingModule({
    imports: [App],
    providers: [{ provide: ApiService, useValue: apiStub(overrides) }],
  });
  const fixture = TestBed.createComponent(App);
  fixture.detectChanges();
  return fixture;
}

describe('App', () => {
  it('shows the sign-in form when not authenticated', () => {
    const el = setup().nativeElement as HTMLElement;
    expect(el.querySelector('h2')?.textContent).toContain('Sign in');
  });

  it('loads resources after a successful login', () => {
    const fixture = setup({
      login: () => of({ token: 't' }),
      resources: () => of({ data: [{ id: 1, branch_id: 1, name: 'Table 1', capacity: 2 }] }),
    });
    const component = fixture.componentInstance;

    component.login();
    fixture.detectChanges();

    expect(component.authed()).toBeTrue();
    expect(component.resources().length).toBe(1);
  });

  it('surfaces a 409 as a slot-taken message', () => {
    const fixture = setup({
      book: () => throwError(() => new HttpErrorResponse({ status: 409 })),
    });
    const component = fixture.componentInstance;
    component.selected.set({ id: 1, branch_id: 1, name: 'Table 1', capacity: 2 });

    component.book();

    expect(component.error()).toContain('already taken');
  });
});
