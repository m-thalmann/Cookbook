import { ChangeDetectionStrategy, Component, Input } from '@angular/core';
import { CookbookWithCounts } from 'src/app/core/models/cookbook';

@Component({
  selector: 'app-cookbook-card',
  templateUrl: './cookbook-card.component.html',
  styleUrls: ['./cookbook-card.component.scss'],
  changeDetection: ChangeDetectionStrategy.OnPush,
})
export class CookbookCardComponent {
  @Input() cookbook!: CookbookWithCounts;

  constructor() {}
}

