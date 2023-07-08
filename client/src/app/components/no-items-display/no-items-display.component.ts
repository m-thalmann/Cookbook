import { CommonModule } from '@angular/common';
import { ChangeDetectionStrategy, Component } from '@angular/core';
import { MatIconModule } from '@angular/material/icon';

const ICONS = [
  'ramen_dining',
  'local_pizza',
  'lunch_dining',
  'fastfood',
  'kitchen',
  'icecream',
  'breakfast_dining',
  'cake',
  'bakery_dining',
  'kebab_dining',
  'dinner_dining',
];

@Component({
  selector: 'app-no-items-display',
  standalone: true,
  imports: [CommonModule, MatIconModule],
  templateUrl: './no-items-display.component.html',
  styleUrls: ['./no-items-display.component.scss'],
  changeDetection: ChangeDetectionStrategy.OnPush,
})
export class NoItemsDisplayComponent {
  get icon(): string {
    return ICONS[Math.floor(Math.random() * ICONS.length)];
  }
}
