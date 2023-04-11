import { ChangeDetectionStrategy, Component, EventEmitter, HostBinding, Input, Output } from '@angular/core';
import { CommonModule } from '@angular/common';
import { MatIconModule } from '@angular/material/icon';
import { MatTooltipModule } from '@angular/material/tooltip';

@Component({
  selector: 'app-recipe-search-filter',
  templateUrl: './recipe-search-filter.component.html',
  styleUrls: ['./recipe-search-filter.component.scss'],
  standalone: true,
  imports: [CommonModule, MatIconModule, MatTooltipModule],
  changeDetection: ChangeDetectionStrategy.OnPush,
  host: {},
})
export class RecipeSearchFilterComponent {
  @HostBinding('class.disabled')
  @Input()
  disabled = false;

  @HostBinding('class.error')
  @Input()
  error?: string;

  @Input() labelText!: string;
  @Input() labelIcon!: string;

  @Output() change = new EventEmitter<Event>();
}

