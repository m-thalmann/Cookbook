import { CommonModule } from '@angular/common';
import { ChangeDetectionStrategy, Component, EventEmitter, Input, Output } from '@angular/core';
import { MatButtonModule } from '@angular/material/button';
import { MatDialog } from '@angular/material/dialog';
import { MatIconModule } from '@angular/material/icon';
import { MatMenuModule } from '@angular/material/menu';
import { MatTooltipModule } from '@angular/material/tooltip';
import { RouterLink } from '@angular/router';
import { TranslocoModule } from '@ngneat/transloco';
import { ShareMenuComponent } from 'src/app/components/share-menu/share-menu.component';
import { SkeletonComponent } from 'src/app/components/skeleton/skeleton.component';
import { DetailedRecipe } from 'src/app/core/models/recipe';
import { RecipePublicShareDialogComponent } from '../recipe-public-share-dialog/recipe-public-share-dialog.component';

@Component({
  selector: 'app-recipe-detail-header',
  templateUrl: './recipe-detail-header.component.html',
  styleUrls: ['./recipe-detail-header.component.scss'],
  standalone: true,
  imports: [
    CommonModule,
    RouterLink,
    TranslocoModule,
    MatIconModule,
    MatButtonModule,
    MatMenuModule,
    MatTooltipModule,
    ShareMenuComponent,
    SkeletonComponent,
  ],
  changeDetection: ChangeDetectionStrategy.OnPush,
})
export class RecipeDetailHeaderComponent {
  @Input() recipe!: DetailedRecipe | null;
  @Input() disabled = false;

  @Output() deleteRecipe = new EventEmitter<void>();

  constructor(private dialog: MatDialog) {}

  openPublicShareDialog() {
    this.dialog.open(RecipePublicShareDialogComponent, { data: { recipe: this.recipe } });
  }
}
