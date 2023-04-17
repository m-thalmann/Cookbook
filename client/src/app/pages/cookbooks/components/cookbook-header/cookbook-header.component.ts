import { CommonModule } from '@angular/common';
import { ChangeDetectionStrategy, Component, Input } from '@angular/core';
import { MatButtonModule } from '@angular/material/button';
import { MatIconModule } from '@angular/material/icon';
import { RouterLink } from '@angular/router';
import { SkeletonComponent } from 'src/app/components/skeleton/skeleton.component';
import { Cookbook, CookbookWithUserMeta } from 'src/app/core/models/cookbook';

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

  @Input() showEditButton = false;
  @Input() showBackButton = false;

  get isAdmin() {
    return this.cookbook && 'meta' in this.cookbook && this.cookbook.meta.is_admin;
  }
}
