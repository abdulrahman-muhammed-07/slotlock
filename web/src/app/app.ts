import { Component, inject, signal } from '@angular/core';
import { FormsModule } from '@angular/forms';
import { HttpErrorResponse } from '@angular/common/http';
import { ApiService, Resource } from './api.service';

@Component({
  selector: 'app-root',
  imports: [FormsModule],
  templateUrl: './app.html',
  styleUrl: './app.css',
})
export class App {
  private readonly api = inject(ApiService);

  readonly authed = signal(this.api.isAuthenticated());
  readonly resources = signal<Resource[]>([]);
  readonly selected = signal<Resource | null>(null);
  readonly bookedSlots = signal<string[]>([]);
  readonly message = signal<string | null>(null);
  readonly error = signal<string | null>(null);

  email = 'owner@northwind.test';
  password = 'password';
  date = new Date().toISOString().slice(0, 10);
  slotTime = '19:00';
  customerName = '';

  login(): void {
    this.error.set(null);
    this.api.login(this.email, this.password).subscribe({
      next: () => {
        this.authed.set(true);
        this.loadResources();
      },
      error: () => this.error.set('Login failed. Check the credentials.'),
    });
  }

  logout(): void {
    this.api.logout();
    this.authed.set(false);
    this.resources.set([]);
    this.selected.set(null);
  }

  loadResources(): void {
    this.api.resources().subscribe({
      next: (res) => this.resources.set(res.data),
      error: () => this.error.set('Could not load resources.'),
    });
  }

  select(resource: Resource): void {
    this.selected.set(resource);
    this.message.set(null);
    this.error.set(null);
    this.refreshAvailability();
  }

  refreshAvailability(): void {
    const resource = this.selected();
    if (!resource) {
      return;
    }
    this.api.availability(resource.id, this.date).subscribe({
      next: (res) => this.bookedSlots.set(res.booked_slots),
      error: () => this.error.set('Could not load availability.'),
    });
  }

  book(): void {
    const resource = this.selected();
    if (!resource) {
      return;
    }
    this.message.set(null);
    this.error.set(null);

    const start = `${this.date}T${this.slotTime}:00`;
    const end = `${this.date}T${this.nextHour(this.slotTime)}:00`;

    this.api
      .book({
        resource_id: resource.id,
        branch_id: resource.branch_id,
        customer_name: this.customerName || 'Guest',
        slot_start: start,
        slot_end: end,
      })
      .subscribe({
        next: (res) => {
          this.message.set(`Booked #${res.data.id} for ${res.data.customer_name}.`);
          this.refreshAvailability();
        },
        error: (err: HttpErrorResponse) => {
          this.error.set(
            err.status === 409
              ? 'That slot is already taken. Pick another time.'
              : 'Booking failed.',
          );
        },
      });
  }

  private nextHour(time: string): string {
    const hour = Number(time.slice(0, 2));
    return `${String((hour + 1) % 24).padStart(2, '0')}:00`;
  }
}
