import { ChangeDetectionStrategy, Component, Input } from '@angular/core';
import { Cookbook, CookbookWithUserMeta } from 'src/app/core/models/cookbook';

@Component({
  selector: 'app-cookbook-header',
  templateUrl: './cookbook-header.component.html',
  styleUrls: ['./cookbook-header.component.scss'],
  changeDetection: ChangeDetectionStrategy.OnPush,
})
export class CookbookHeaderComponent {
  @Input() cookbook: Cookbook | CookbookWithUserMeta | null = null;

  @Input() showEditButton = false;
  @Input() showBackButton = false;

  get isAdmin() {
    return this.cookbook && 'meta' in this.cookbook && this.cookbook.meta.is_admin;
  }
}

