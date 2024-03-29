import { CommonModule } from '@angular/common';
import { ChangeDetectionStrategy, Component } from '@angular/core';
import { MatIconModule } from '@angular/material/icon';
import { TranslocoModule } from '@ngneat/transloco';
import { CreateRecipeDetailsComponent } from './components/create-recipe-details/create-recipe-details.component';

@Component({
  selector: 'app-create-recipe-page',
  standalone: true,
  imports: [CommonModule, TranslocoModule, MatIconModule, CreateRecipeDetailsComponent],
  templateUrl: './create-recipe-page.component.html',
  styleUrls: ['./create-recipe-page.component.scss'],
  changeDetection: ChangeDetectionStrategy.OnPush,
})
export class CreateRecipePageComponent {}
