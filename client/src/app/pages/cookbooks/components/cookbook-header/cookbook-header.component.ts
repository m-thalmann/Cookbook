import { CommonModule } from '@angular/common';
import { ChangeDetectionStrategy, Component, Input } from '@angular/core';
import { MatButtonModule } from '@angular/material/button';
import { MatIconModule } from '@angular/material/icon';
import { RouterLink } from '@angular/router';
import { SkeletonComponent } from 'src/app/components/skeleton/skeleton.component';
import { CoerceBooleanProperty } from 'src/app/core/helpers/coerce-boolean-property';
import { Cookbook, CookbookWithUserMeta } from 'src/app/core/models/cookbook';
import { RouteHelperService } from 'src/app/core/services/route-helper.service';

@Component({
  selector: 'app-cookbook-header',
  templateUrl: './cookbook-header.component.html',
  styleUrls: ['./cookbook-header.component.scss'],
  standalone: true,
  imports: [CommonModule, RouterLink, MatButtonModule, MatIconModule, SkeletonComponent],
  changeDetection: ChangeDetectionStrategy.OnPush,
})
export class CookbookHeaderComponent {
  @Input() cookbook: Cookbook | CookbookWithUserMeta | null = null;

  @Input()
  @CoerceBooleanProperty()
  showEditButton: any = false;
  @Input()
  @CoerceBooleanProperty()
  showBackButton: any = false;

  constructor(public routeHelper: RouteHelperService) {}

  get isAdmin() {
    return this.cookbook && 'meta' in this.cookbook && this.cookbook.meta.is_admin;
  }
}
