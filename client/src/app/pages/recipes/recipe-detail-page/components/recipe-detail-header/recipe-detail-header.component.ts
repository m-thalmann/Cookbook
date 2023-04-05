import { ChangeDetectionStrategy, Component, EventEmitter, Input, Output } from '@angular/core';
import { MatDialog } from '@angular/material/dialog';
import { DetailedRecipe } from 'src/app/core/models/recipe';
import { RecipePublicShareDialogComponent } from '../recipe-public-share-dialog/recipe-public-share-dialog.component';
import { CommonModule } from '@angular/common';
import { RouterLink } from '@angular/router';
import { MatIconModule } from '@angular/material/icon';
import { MatMenuModule } from '@angular/material/menu';
import { ShareMenuComponent } from 'src/app/components/share-menu/share-menu.component';
import { MatButtonModule } from '@angular/material/button';

@Component({
  selector: 'app-recipe-detail-header',
  templateUrl: './recipe-detail-header.component.html',
  styleUrls: ['./recipe-detail-header.component.scss'],
  standalone: true,
  imports: [CommonModule, RouterLink, MatIconModule, MatButtonModule, MatMenuModule, ShareMenuComponent],
  changeDetection: ChangeDetectionStrategy.OnPush,
})
export class RecipeDetailHeaderComponent {
  @Input() recipe!: DetailedRecipe;
  @Input() disabled = false;

  @Output() deleteRecipe = new EventEmitter<void>();

  constructor(private dialog: MatDialog) {}

  openPublicShareDialog() {
    this.dialog.open(RecipePublicShareDialogComponent, { data: { recipe: this.recipe } });
  }
}
